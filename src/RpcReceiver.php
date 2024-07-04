<?php

namespace Fernandod1\RabbitmqRpc;

require_once '../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Dotenv\Dotenv;

class RpcReceiver
{
    /**
     * Listens for incoming messages
     */
    public function listen()
    {
        $dotenv = Dotenv::createImmutable('../');
        $dotenv->safeLoad();
        $connection = new AMQPStreamConnection( $_ENV["RABBITMQ_HOST"], 
		                                        $_ENV["RABBITMQ_PORT"], 
		                                        $_ENV["RABBITMQ_USERNAME"], 
		                                    $_ENV["RABBITMQ_PASSWORD"]
                                            );
        $channel = $connection->channel();

        $channel->queue_declare(
            'rpc_queue',    #queue 
            false,          #passive
            false,          #durable
            false,          #exclusive
            false           #autodelete
        );

        $channel->basic_qos(
            null,   #prefetch size
            1,      #prefetch count
            null    #global
        );

        $channel->basic_consume(
            'rpc_queue',                #queue
            '',                         #consumer tag
            false,                      #no local
            false,                      #no ack
            false,                      #exclusive
            false,                      #no wait
            array($this, 'callback')    #callback
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    /**
     * Executes when a message is received.
     *
     * @param AMQPMessage $req
     */
    public function callback(AMQPMessage $req)
    {
        $credentials = json_decode($req->body);
        $authResult = $this->auth($credentials);

        echo "Received credentials. Sent back auth result.\n";

        /*
    	 * Creating a reply message with the same correlation id than the incoming message
    	 */
        $msg = new AMQPMessage(
            json_encode(array('status' => $authResult)),            #message
            array('correlation_id' => $req->get('correlation_id'))  #options
        );

        /*
    	 * Publishing to the same channel from the incoming message
    	 */
        $req->delivery_info['channel']->basic_publish(
            $msg,                   #message
            '',                     #exchange
            $req->get('reply_to')   #routing key
        );

        /*
    	 * Acknowledging the message
    	 */
        $req->delivery_info['channel']->basic_ack(
            $req->delivery_info['delivery_tag'] #delivery tag
        );
    }

    /**
     * @param \stdClass $credentials
     * @return bool
     */
    private function auth(\stdClass $credentials)
    {
        if (($credentials->username == $_ENV["REAL_USERNAME"]) && ($credentials->password == $_ENV["REAL_PASSWORD"])) {
            return true;
        } else {
            return false;
        }
    }
}
