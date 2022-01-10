<?php

namespace OndraKoupil\AppTools\FileManager\Images;

class ImageVersion {

	protected string $id;

	protected ImageTransformationInterface $transformation;

	/**
	 * @param string $id
	 * @param ImageTransformationInterface $transformation
	 */
	public function __construct(string $id, ImageTransformationInterface $transformation) {
		$this->id = $id;
		$this->transformation = $transformation;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return ImageTransformationInterface
	 */
	public function getTransformation(): ImageTransformationInterface {
		return $this->transformation;
	}

}
