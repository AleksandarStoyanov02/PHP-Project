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
    protected $conn;

    public function __construct($dbConnection) {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->conn = $dbConnection;
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
                $this->handleJoin($from, $roomId, $userId);
                break;
            case 'start_timer':
                $this->handleStartTimer($from, $roomId, $data['duration']);
                break;
            case 'play_sound':
                $this->handlePlaySound($from, $roomId, $data['sound'], $data['row'], $data['col']);
                break;
            case 'get_timer_state':
                $this->sendTimerState($from, $roomId);
                break;
            case 'refresh_page':
                $this->handleRefreshPage($roomId);
                break;
        }
    }

    private function handleRefreshPage($roomId) {
        $this->broadcastToRoom($roomId, json_encode(['type' => 'refresh_page']));
    }

    private function handleJoin(ConnectionInterface $conn, $roomId, $userId) {
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = new \SplObjectStorage;
        }
        $this->rooms[$roomId]->attach($conn, $userId);
        $this->sendTimerState($conn, $roomId);
    }

    private function handleStartTimer(ConnectionInterface $from, $roomId, $duration) {
        $startTime = time();
        $endTime = $startTime + $duration;

        // Store timer in the database
        $stmt = $this->conn->prepare("INSERT INTO room_timers (room_id, start_time, end_time) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE start_time = ?, end_time = ?");
        $stmt->bind_param("iiiii", $roomId, $startTime, $endTime, $startTime, $endTime);
        $stmt->execute();
        $stmt->close();

        $this->broadcastToRoom($roomId, json_encode([
            'type' => 'start_timer',
            'duration' => $duration,
            'start_time' => $startTime
        ]));
    }

    private function sendTimerState(ConnectionInterface $conn, $roomId) {
        $stmt = $this->conn->prepare("SELECT start_time, end_time FROM room_timers WHERE room_id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $timer = $result->fetch_all(MYSQLI_ASSOC)[0];
        $startTime = $timer['start_time'];
        $endTime = $timer['end_time'];

        if ($startTime && $endTime) {
            $currentTime = time();
            $remainingTime = $endTime - $currentTime;
            if ($remainingTime > 0) {
                $conn->send(json_encode([
                    'type' => 'timer_state',
                    'duration' => $remainingTime,
                    'start_time' => $startTime
                ]));
            } else {
                echo "izteche";
                $conn->send(json_encode(['type' => 'timer_expired']));
            }
        } else {
            $conn->send(json_encode(['type' => 'no_timer']));
        }
    }

    private function broadcastToRoom($roomId, $message) {
        if (isset($this->rooms[$roomId])) {
            foreach ($this->rooms[$roomId] as $client) {
                $client->send($message);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        foreach ($this->rooms as $roomId => $clients) {
            if ($clients->contains($conn)) {
                $clients->detach($conn);
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
            new RoomServer($conn)
        )
    ),
    8000
);

$server->run();
?>