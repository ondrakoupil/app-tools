<?php

namespace OndraKoupil\AppTools\FileManager\Images;

interface ImageTransformationInterface {

	function transform(string $path): void;

}
