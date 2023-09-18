<?php

namespace OndraKoupil\AppTools\Importing\Writer;

interface WriterInterface {

	function startWriting(): void;
	function endWriting(): void;
	function write($item): void;
	function getCurrentPosition();

}
