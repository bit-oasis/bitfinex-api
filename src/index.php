<?php
$composerAutoloader = __DIR__ . '/../vendor/autoload.php';

if (!is_file($composerAutoloader)) {
	die(
		'You need to set up the project dependencies using the following commands:' . PHP_EOL .
		'curl -sS https://getcomposer.org/installer | php' . PHP_EOL .
		'php composer.phar install' . PHP_EOL
	);
}

require_once $composerAutoloader;



//$httpClient = new \BitOasis\Bitfinex\Http\GuzzleClient(new \GuzzleHttp\Client());
//$restClient = new \BitOasis\Bitfinex\Rest\RestClient('gIWrexcNI5ZjPKnHZT2rqPcc7se3kfIba3b2IA1ETaw', '5WIcH0985naCmk5uWBAWk33B8RlJ6gaMG965rxMzJ8O', $httpClient);
//
//$ticker = new \BitOasis\Bitfinex\Rest\Ticker($restClient);

$loop = \React\EventLoop\Factory::create();

$websocket = new \BitOasis\Bitfinex\Websocket\BitfinexWebsocket('...', '...', $loop);

$orderBookChannel = new \BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookChannel(\BitOasis\Bitfinex\Constant\Symbol::TBTCUSD, 'P0', 'F0', 250, $loop);



class X implements \BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookChannelSubscriber {

	/** @var \BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookMessage[] */
	protected $bids;

	/** @var \BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookMessage[] */
	protected $asks;

	public function __construct() {
		$this->bids = [];
		$this->asks = [];
	}


	public function onOrderBookUpdateReceived(\BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookMessage $message) {
		$fromTime = microtime(true);
		$pow = 5;
		if ($message->shouldBeRemoved()) {
			if (isset($this->bids[$message->getBidKey($pow)])) {
				unset($this->bids[$message->getBidKey($pow)]);
			}
			if (isset($this->asks[$message->getAskKey($pow)])) {
				unset($this->asks[$message->getAskKey($pow)]);
			}
		} else {
			if ($message->isBid()) {
				$this->bids[$message->getBidKey($pow)] = $message;
				krsort($this->bids);
				foreach($this->asks as $key => $ask) {
					if ($ask->getPrice() <= $message->getPrice()) {
						unset($this->asks[$key]);
					} else {
						break;
					}
				}
			} else if ($message->isAsk()) {
				$this->asks[$message->getAskKey($pow)] = $message;
				ksort($this->asks);
				foreach($this->bids as $key => $bid) {
					if ($bid->getPrice() >= $message->getPrice()) {
						unset($this->bids[$key]);
					} else {
						break;
					}
				}
			}
		}
//		echo(sprintf('%5d', round((microtime(true) - $fromTime) * 1000)) . ' ms' . "\n");
//		echo(".");
//		echo("Changed {$message->getPrice()} USD to {$message->getAmount()} BTC (" . count($this->bids) . ", " . count($this->asks) . ")\n");
	}

	public function onOrderBookStarted() {
		echo("\nSTARTED!\n");
	}

	public function onOrderBookStopped() {
		echo("\nSTOPPED!\n");
	}

	public function clearOrderBook() {
		$this->bids = [];
		$this->asks = [];
		echo("\nCLEAR!\n");
	}

	public function printBook($decPoints = 2) {
		/** @var \BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookMessage[] $bids */
		$bids = [];
		/** @var \BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookMessage[] $asks */
		$asks = [];

//		system('clear');
		$fromTime = microtime(true);

	    foreach($this->bids as $message) {
	    	$key = $message->getBidKey($decPoints);
	    	if (isset($bids[$key])) {
			    $bids[$key] = $message->add($bids[$key]);
		    } else {
			    $bids[$key] = $message;
		    }
	    	if (count($bids) >= 10) {
	    		break;
		    }
	    }
	    krsort($bids);
	    $bids = array_values($bids);

		foreach($this->asks as $message) {
			$key = $message->getAskKey($decPoints);
			if (isset($asks[$key])) {
				$asks[$key] = $message->add($asks[$key]);
			} else {
				$asks[$key] = $message;
			}
			if (count($asks) >= 10) {
				break;
			}
		}
		ksort($asks);
		$asks = array_values($asks);

		echo(sprintf('%5d', round((microtime(true) - $fromTime) * 1000)) . ' ms' . "\n");
	    echo(date('Y-m-d H:i:s') . "\n");
	    for ($i = 0, $iMax = min(count($bids), count($asks)); $i < $iMax; $i++) {
	    	$bid = $bids[$i];
	    	$ask = $asks[$i];
			printf("%2d  %8.2f  %8.2f  |  %8.2f  %8.2f  %2d\n", $bid->getCount(), $bid->getRoundedAmount($decPoints+1), $bid->getRoundedBidPrice($decPoints), $ask->getRoundedAskPrice($decPoints), $ask->getRoundedAmount($decPoints+1), $ask->getCount());
	    }
	}

	public function printCleanBook() {
		/** @var \BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookMessage[] $bids */
		$bids = [];
		/** @var \BitOasis\Bitfinex\Websocket\Channel\OrderBook\OrderBookMessage[] $asks */
		$asks = [];

		system('clear');
		$fromTime = microtime(true);
		$i = 0;
		foreach($this->bids as $message) {
			$bids[] = $message;
			if ($i++ > 10) {
				break;
			}
		}
		$i = 0;
		foreach($this->asks as $message) {
			$asks[] = $message;
			if ($i++ > 10) {
				break;
			}
		}

		echo(sprintf('%5d', round((microtime(true) - $fromTime) * 1000)) . ' ms' . "\n");
		echo(date('Y-m-d H:i:s') . "\n");
		echo(count($this->bids) . " / " . count($this->asks) . "\n");
		for ($i = 0, $iMax = min(count($bids), count($asks)); $i < $iMax; $i++) {
			$bid = $bids[$i];
			$ask = $asks[$i];
			printf("%2d  %8.2f  %8.2f  |  %8.2f  %8.2f  %2d\n", $bid->getCount(), $bid->getAmount(), $bid->getPrice(), $ask->getPrice(), $ask->getAmount(), $ask->getCount());
		}
	}
}

$x = new X();
//$loop->addPeriodicTimer(0.5, function() use($x) {
//	$x->printCleanBook();
//	$x->printBook(0);
//});

class Logger extends \Psr\Log\AbstractLogger {

	public function log($level, $message, array $context = array()) {
		if ($level === \Psr\Log\LogLevel::DEBUG) {
//			return;
		}
		$replace = array();
		foreach ($context as $key => $val) {
			if (is_array($val)) {
				$val = \Nette\Utils\Json::encode($val);
			} else if (is_object($val)) {
				$val = method_exists($val, '__toString') ? (string)$val : get_class($val);
			}
			$replace['{' . $key . '}'] = $val;
		}
		$message = strtr($message, $replace);
		echo(date('Y-m-d H:i:s') . ' - ' . $message . "\n");
	}
}
$logger = new Logger();
$websocket->setLogger($logger);

$orderBookChannel->addOrderBookChannelSubscriber($x);

//$websocket->addSubscriber($orderBookChannel);


$inputChannel = new \BitOasis\Bitfinex\Websocket\Channel\Input\InputChannel();
$websocket->addSubscriber($inputChannel);

$loop->addTimer(5, function() use($inputChannel) {
	$cancelOrder = \BitOasis\Bitfinex\Websocket\Channel\Input\Operation\CancelOrder::fromCid(28804, new DateTime());
	$inputChannel->process($cancelOrder)->done(function($a) {
		echo("\nOK\n");
	}, function($x) {
		print_r($x);
		echo("\nFAIL\n");
	});
//	$newOrder = new \BitOasis\Bitfinex\Websocket\Channel\Input\Operation\NewOrder(rand(10, 100000), \BitOasis\Bitfinex\Constant\OrderType::EXCHANGE_LIMIT, \BitOasis\Bitfinex\Constant\Symbol::TBTCUSD, 0.005, rand(10,100));
//	$newOrder->setGid(1);
//	$inputChannel->process($newOrder)->done(function($a) {
//		print_r($a);
//		echo("\nOK\n");
//	}, function($x) {
//		print_r($x);
//		echo("\nFAIL\n");
//	});
});

//$websocket->setOrigin('bitoasis.net');

$websocket->connect();

$loop->run();
