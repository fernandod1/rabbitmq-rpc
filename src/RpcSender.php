<?php

namespace Fernandod1\RabbitmqRpc;

require_once '../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RpcSender
{
	private $response;

	/**
	 * @var string
	 */
	private $corr_id;

	/**
	 * @param array $credentials
	 * @return string
	 */
	public function execute($credentials)
	{
		$connection = new AMQPStreamConnection(	$_ENV["RABBITMQ_HOST"], 
												$_ENV["RABBITMQ_PORT"], 
												$_ENV["RABBITMQ_USERNAME"], 
											$_ENV["RABBITMQ_PASSWORD"]
											);
		$channel = $connection->channel();

		/*
		 * creates an anonymous exclusive callback queue
		 * $callback_queue has a value like amq.gen-_U0kJVm8helFzQk9P0z9gg
		 */
		list($callback_queue,,) = $channel->queue_declare(
			"", 	#queue
			false, 	#passive
			false, 	#durable
			true, 	#exclusive
			false	#auto delete
		);

		$channel->basic_consume(
			$callback_queue, 					#queue
			'', 								#consumer tag
			false, 								#no local
			false, 								#no ack
			false, 								#exclusive
			false, 								#no wait
			array($this, 'onReceivedResponse')	#callback
		);

		$this->response = null;

		/*
		 * $this->corr_id has a value like 53e26b393313a
		 */
		$this->corr_id = uniqid();
		$jsonCredentials = json_encode($credentials);

		/*
		 * create a message with two properties: reply_to, which is set to the 
		 * callback queue and correlation_id, which is set to a unique value for 
		 * every request
		 */
		$msg = new AMQPMessage(
			$jsonCredentials,    #body
			array('correlation_id' => $this->corr_id, 'reply_to' => $callback_queue)    #properties
		);

		/*
		 * The request is sent to an rpc_queue queue.
		 */
		$channel->basic_publish(
			$msg,		#message 
			'', 		#exchange
			'rpc_queue'	#routing key
		);

		while (!$this->response) {
			$channel->wait();
		}

		$channel->close();
		$connection->close();

		return $this->response;
	}

	/**
	 * When a message appears, it checks the correlation_id property. If it
	 * matches the value from the request it returns the response to the
	 * application.
	 *
	 * @param AMQPMessage $rep
	 */
	public function onReceivedResponse(AMQPMessage $rep)
	{
		if ($rep->get('correlation_id') == $this->corr_id) {
			$this->response = $rep->body;
		}
	}
}
