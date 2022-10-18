<?php
//© 2022 Martin Peter Madsen
namespace MTM\Utilities\Models\Processes\BashShell;

abstract class Commands extends Base
{
	protected $_maxArgLen=null;
	
	public function execute($strCmd, $delim=null, $timeout=10000, $throw=true)
	{
		if ($delim !== null) {
			$rData		= $this->getProc()->write($strCmd)->readRegEx($delim, $timeout);
		} else {
			$rData		= $this->getProc()->write($strCmd)->read($timeout, true);
		}
		if ($rData->error !== null && $throw === true) {
			throw new \Exception("Command failed with error: '".$rData->error."'");
		}
		return $this->parseData($rData->data, $delim);
	}
	public function getMaxArg($refresh=false)
	{
		if ($this->_maxArgLen === null || $refresh === true) {
			$strCmd		= "getconf ARG_MAX; echo -en \"MtmEcho\"";
			$data		= $this->execute($strCmd, "MtmEcho");
			if (preg_match("/([0-9]+)/", $data, $raw) === 1) {
				$this->_maxArgLen	= intval($raw[1]);
			} else {
				throw new \Exception("Failed to determine max argument length");
			}
		}
		return $this->_maxArgLen;
	}
	protected function parseData($data, $delim=null)
	{
		$dLen	= strlen($data);
		if ($dLen > 0) {
			if ($delim !== null) {
				$dPos	= strrpos($data, $delim);
				if ($dPos !== false) {
					$nLen	= strlen($delim);
					$nPos	= ($dPos+$nLen);
					if ($nPos === $dLen) {
						$data	= substr($data, 0, $dPos);
					} elseif (($nPos + 1) === $dLen && substr($data, -1) === "\n") {
						$data	= substr($data, 0, $dPos);
					}
				}
			}
			if (substr($data, -1) === "\n") {
				//if the last char is a line break remove it
				$data	= substr($data, 0, -1);
			}
		}
		return $data;
	}
}