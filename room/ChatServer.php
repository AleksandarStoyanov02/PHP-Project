<?php
require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class RoomServer implements MessageComponentInterface {
    protected SplObjectStorage $clients;
    protected array $rooms;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $roomId = $data['room_id'];
        $userId = $data['user_id'];

        switch($data['type']) {
            case 'join':
                if (!isset($this->rooms[$roomId])) {
                    $this->rooms[$roomId] = new \SplObjectStorage;
                }

                $this->rooms[$roomId]->attach($from, $userId);
                break;

            case 'start_timer':
                if (isset($this->rooms[$roomId])) {
                    foreach ($this->rooms[$roomId] as $client) {
                        if ($client !== $from) {
                            $client->send(json_encode([
                                'type' => 'start_timer',
                                'duration' => $data['duration'],
                                'start_time' => $data['start_time']
                            ]));
                        }
                    }
                }
                break;

            case 'play_sound':
                if (isset($this->rooms[$roomId])) {
                    foreach ($this->rooms[$roomId] as $client) {
                        if ($client !== $from) {
                            $client->send(json_encode([
                                'type' => 'play_sound',
                                'sound' => $data['sound'],
                                'row' => $data['row'],
                                'col' => $data['col']
                            ]));
                        }
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $connn) {
        $this->clients->detach($connn);
        foreach ($this->rooms as $roomId => $clients) {
            if ($clients->contains($connn)) {
                $userId = $clients[$connn];
                $clients->detach($connn);
                // Remove the user from the room_participants table
                global $conn;
                $stmt = $conn->prepare("DELETE FROM room_participants WHERE room_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $roomId, $userId);
                $stmt->execute();

                if (count($clients) == 0) {
                    unset($this->rooms[$roomId]);
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new RoomServer()
        )
    ),
    8000
);

$server->run();
?>