<?php

/**
 * This file is part of the PHP WebRTC package.
 *
 * (c) Amin Yazdanpanah <https://www.aminyazdanpanah.com/#contact>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webrtc\MDNS;

use Amp\Socket\InternetAddress;
use Amp\CancelledException;
use Amp\TimeoutCancellation;
use LibDNS\Decoder\Decoder;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\Encoder;
use LibDNS\Encoder\EncoderFactory;
use LibDNS\Messages\MessageFactory;
use LibDNS\Messages\MessageTypes;
use LibDNS\Records\QuestionFactory;
use LibDNS\Records\ResourceQTypes;
use Throwable;
use Webrtc\Exception\RuntimeException;
use function Amp\Socket\bindUdpSocket;

/**
 * Resolves names over Multicast DNS.
 *
 * WebRTC peers publish their host candidates as randomly generated `.local` names rather than
 * bare IP addresses, so an ICE agent has to answer those names on the local network: there is
 * no upstream server to ask.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6762 Multicast DNS
 */
class MulticastExecutor
{
    private readonly Encoder $encoder;
    private readonly Decoder $decoder;
    private readonly MessageFactory $messageFactory;
    private readonly QuestionFactory $questionFactory;
    private readonly InternetAddress $nameserver;

    /**
     * @param string $nameserver Multicast DNS group and port (default: 224.0.0.251:5353).
     * @param float $timeout How long to wait for an answer, in seconds.
     */
    public function __construct(
        string $nameserver = Factory::DNS,
        private readonly float $timeout = 5.0
    ) {
        $this->nameserver = InternetAddress::fromString($nameserver);
        $this->encoder = (new EncoderFactory())->create();
        $this->decoder = (new DecoderFactory())->create();
        $this->messageFactory = new MessageFactory();
        $this->questionFactory = new QuestionFactory();
    }

    /**
     * Ask the local network for the address behind a name.
     *
     * @param string $name The name to resolve, normally a WebRTC `.local` candidate.
     * @return string The address from the first matching answer record.
     * @throws RuntimeException If nobody answered in time, or the socket closed first.
     */
    public function resolve(string $name): string
    {
        $socket = bindUdpSocket('0.0.0.0:0');

        try {
            $socket->send($this->nameserver, $this->encodeQuery($name));

            // Responders other than the one being asked for also answer on this group, so keep
            // reading until an answer actually carries the name in question rather than taking
            // whichever datagram arrives first.
            $cancellation = new TimeoutCancellation($this->timeout);

            while (($received = $socket->receive($cancellation)) !== null) {
                [, $data] = $received;

                $address = $this->addressFromResponse($data, $name);
                if ($address !== null) {
                    return $address;
                }
            }

            throw new RuntimeException(sprintf('The mDNS socket closed while resolving %s', $name));
        } catch (CancelledException) {
            throw new RuntimeException(sprintf('DNS query for %s timed out', $name));
        } finally {
            $socket->close();
        }
    }

    /**
     * Build the query datagram for a name.
     */
    private function encodeQuery(string $name): string
    {
        $question = $this->questionFactory->create(ResourceQTypes::A);
        $question->setName($name);

        $request = $this->messageFactory->create(MessageTypes::QUERY);
        $request->getQuestionRecords()->add($question);
        // RFC 6762 section 18.1: a multicast query carries no transaction id, because responses
        // are matched on the question rather than on the id.
        $request->setID(0);

        return $this->encoder->encode($request);
    }

    /**
     * Pull the address for a name out of a response, or null if it is not about that name.
     */
    private function addressFromResponse(string $data, string $name): ?string
    {
        try {
            $response = $this->decoder->decode($data);
        } catch (Throwable) {
            // Another responder's unrelated or malformed traffic: keep waiting.
            return null;
        }

        foreach ($response->getAnswerRecords() as $record) {
            // Names come back fully qualified, i.e. with the root label's trailing dot.
            if (strcasecmp(rtrim((string) $record->getName(), '.'), rtrim($name, '.')) !== 0) {
                continue;
            }

            foreach ($record->getData() as $field) {
                return (string) $field;
            }
        }

        return null;
    }
}
