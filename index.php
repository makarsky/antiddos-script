<?php

class AntiDDOS {
	const BAN_LIST = 'lists/banlist.txt';
	const IP_LIST = 'lists/iplist.txt';
	const BAN_TIME_SECONDS = 600;
	const REQUEST_PERIOD_SECONDS = 60;
	const MAX_REQUESTS_PER_PERIOD = 5;

	private $ip;
	private $requestDate;
	private $banList;
	private $ipList;
	private $timeDiff = 0;

	public function __construct()
	{
		$this->requestDate = new DateTime();
		$this->loadBanList();
		$this->loadIpList();
	}

	public function setIp(string $ip): void
	{
		$this->ip = $ip;
	}

	public function check()
	{
		if ($this->isBanned()) {
			$this->showTimeout();
		} else {
			if ($this->saveIpData()) {
				$this->render();
			} else {
				$this->showTimeout();
			}
		}
	}

	protected function saveIpData(): bool
	{
		$now = new DateTime();

		foreach($this->ipList as $key => $ipDataString) {
			if (strpos($ipDataString, $this->ip . ' ') === 0) {
				$ipData = explode(' ', $ipDataString);
				$firstAccessDate = DateTime::createFromFormat('Y-m-d\TH:i:s', $ipData[1]);
				$diff = $now->getTimestamp() - $firstAccessDate->getTimestamp();

				if ($diff < self::REQUEST_PERIOD_SECONDS) {
					$ipData[2] = intval($ipData[2]) + 1;
					$this->ipList[$key] = implode(' ', $ipData);

					if ($ipData[2] > self::MAX_REQUESTS_PER_PERIOD) {
						array_splice($this->ipList, $key, 1);
						$this->banList[] = $this->ip . ' ' . $now->format('Y-m-d\TH:i:s');

						
						return false;
					}
				} else {
					$ipData[1] = $now->format('Y-m-d\TH:i:s');
					$ipData[2] = 1;
					$this->ipList[$key] = implode(' ', $ipData);
				}

				return true;
			}
		}
		
		$ipData = $this->ip . ' ' . $now->format('Y-m-d\TH:i:s') . " 1";
		$this->ipList[] = $ipData;

		return true;
	}

	protected function render(): void
	{
		echo 'Hello World!';
	}

	protected function showTimeout(): void
	{
		$timeout = self::BAN_TIME_SECONDS - $this->timeDiff;

		http_response_code(429); // Too Many Requests
		header('Retry-After: ' . $timeout);
	}

	protected function isBanned()
	{
		foreach($this->banList as $key => $ipDataString) {
			if (strpos($ipDataString, $this->ip . ' ') === 0) {
				$ipData = explode(' ', $ipDataString);

				$banStartDate = DateTime::createFromFormat('Y-m-d\TH:i:s', $ipData[1]);
				$now = new DateTime();
				$this->timeDiff = $now->getTimestamp() - $banStartDate->getTimestamp();

				if ($this->timeDiff < self::BAN_TIME_SECONDS) {
					return true;
				} else {
					array_splice($this->banList, $key, 1);

					return false;
				}
			}
		}

		return false;
	}

	protected function ban()
	{
		return 1;
	}

	protected function loadIpList()
	{
		$this->ipList = explode("\n", file_get_contents(self::IP_LIST));
	}

	protected function saveIpList()
	{
		file_put_contents(self::IP_LIST, implode("\n", $this->ipList));
	}

	protected function loadBanList(): void
	{
		$this->banList = explode("\n", file_get_contents(self::BAN_LIST));
	}

	protected function saveBanList(): void
	{
		file_put_contents(self::BAN_LIST, implode("\n", $this->banList));
	}

	function __destruct() {
        $this->saveBanList();
        $this->saveIpList();
    }
}

$antiDDOS = new AntiDDOS();
$antiDDOS->setIp($_SERVER['REMOTE_ADDR']);
$antiDDOS->check();