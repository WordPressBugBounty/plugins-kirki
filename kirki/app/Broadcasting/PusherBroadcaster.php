<?php

namespace Kirki\App\Broadcasting;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\Broadcasting\Contracts\Broadcaster;
use Kirki\App\DTO\Collaboration\CreateCollaborationDTO;
use Kirki\Framework\Collections\Collection;
use Kirki\Framework\Supports\Facades\Log;
use Pusher\Pusher;

/**
 * Publishes collaboration actions over the Pusher protocol.
 *
 * Works with Pusher Channels and with any compatible server such as
 * Laravel Reverb or soketi, the host is only a configuration value.
 */
class PusherBroadcaster implements Broadcaster
{
    /**
     * Events accepted by the server in a single batch request.
     */
    const BATCH_LIMIT = 10;

    /**
     * Maximum payload size (in bytes) before chunking. Pusher limit is 10,240 bytes.
     * 8,000 leaves plenty of room for JSON envelope and metadata overhead.
     */
    const CHUNK_SIZE = 8000;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Pusher
     */
    protected $pusher;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function broadcast(Collection $events, bool $cleanup = true)
    {
        $batch = [];

        foreach ($events as $dto) {
            $channel     = Channel::name($dto->parent, $dto->parent_id);
            $action_json = wp_json_encode($dto->data);
            $size        = strlen($action_json);

            if ($size > self::CHUNK_SIZE) {
                $chunks       = $this->split_string_safe($action_json, self::CHUNK_SIZE);
                $total_chunks = count($chunks);
                $message_id   = uniqid('chunk_', true);

                foreach ($chunks as $index => $chunk) {
                    $batch[] = [
                        'channel' => $channel,
                        'name'    => Channel::EVENT,
                        'data'    => [
                            'session_id' => $dto->session_id,
                            'chunked'    => true,
                            'id'         => $message_id,
                            'index'      => $index,
                            'total'      => $total_chunks,
                            'chunk'      => $chunk,
                        ],
                    ];
                }
            } else {
                $batch[] = [
                    'channel' => $channel,
                    'name'    => Channel::EVENT,
                    'data'    => [
                        'session_id' => $dto->session_id,
                        'action'     => $dto->data,
                    ],
                ];
            }
        }

        try {
            foreach (array_chunk($batch, self::BATCH_LIMIT) as $chunk) {
                $this->pusher()->triggerBatch($chunk);
            }
        } catch (Exception $e) {
            Log::error('Collaboration broadcast failed: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Split a string safely by byte length without breaking multi-byte UTF-8 sequences.
     *
     * @param string $string
     * @param int    $chunk_size
     * @return array<string>
     */
    protected function split_string_safe($string, $chunk_size = self::CHUNK_SIZE)
    {
        $chunks = [];
        $offset = 0;
        $len    = strlen($string);

        while ($offset < $len) {
            if (function_exists('mb_strcut')) {
                $chunk = mb_strcut($string, $offset, $chunk_size, 'UTF-8');
            } else {
                $chunk = substr($string, $offset, $chunk_size);
            }

            if ($chunk === '' || $chunk === false) {
                break;
            }
            $chunks[] = $chunk;
            $offset  += strlen($chunk);
        }

        return $chunks;
    }

    /**
     * {@inheritDoc}
     */
    public function client_config()
    {
        // Options are passed to the pusher-js client as they are.
        $options = [
            'cluster'           => $this->config['cluster'],
            'forceTLS'          => 'https' === $this->config['scheme'],
            'enabledTransports' => ['ws', 'wss'],
        ];

        // Without a host the client talks to the Pusher cloud of the cluster.
        if (!empty($this->config['host'])) {
            $options['wsHost']  = $this->config['host'];
            $options['wsPort']  = $this->config['port'];
            $options['wssPort'] = $this->config['port'];
        }

        return [
            'driver'  => 'pusher',
            'key'     => $this->config['key'],
            'options' => $options,
        ];
    }

    /**
     * Sign a subscription request of a private or presence channel.
     *
     * @param string $socket_id
     * @param string $channel
     * @param array  $member Presence member data.
     *
     * @return array
     */
    public function authenticate($socket_id, $channel, array $member)
    {
        $auth = Channel::is_presence($channel)
            ? $this->pusher()->authorizePresenceChannel($channel, $socket_id, $member['user_id'], $member['user_info'])
            : $this->pusher()->authorizeChannel($channel, $socket_id);

        return json_decode($auth, true);
    }

    /**
     * @return Pusher
     */
    protected function pusher()
    {
        if (!$this->pusher) {
            $options = [
                'cluster' => $this->config['cluster'],
                'useTLS'  => 'https' === $this->config['scheme'],
            ];

            // Without a host the SDK talks to the Pusher cloud of the cluster.
            if (!empty($this->config['host'])) {
                $options['host']   = $this->config['host'];
                $options['port']   = $this->config['port'];
                $options['scheme'] = $this->config['scheme'];
            }

            $this->pusher = new Pusher(
                $this->config['key'],
                $this->config['secret'],
                $this->config['app_id'],
                $options
            );
        }

        return $this->pusher;
    }
}
