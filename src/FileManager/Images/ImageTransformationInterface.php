<?php

namespace OndraKoupil\AppTools\FileManager\Images;

interface ImageTransformationInterface {

	/**
	 * @param resource $resource A GD-resource
	 * @return mixed $resource A GD-resource
	 */
	function transform($resource);

}
