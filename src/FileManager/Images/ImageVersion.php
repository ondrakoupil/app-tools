<?php

namespace OndraKoupil\AppTools\FileManager\Images;

use OndraKoupil\Tools\Arrays;

class ImageVersion {

	protected string $id;

	/**
	 * @var ImageTransformationInterface[]
	 */
	protected array $transformations;

	protected int $quality;

	/**
	 * @param string $id
	 * @param null $transformations
	 * @param int $quality
	 */
	public function __construct(string $id, $transformations = null, int $quality = 85) {
		$this->id = $id;
		foreach (Arrays::arrayize($transformations) as $t) {
			$this->addTransformation($t);
		}
		$this->quality = $quality;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getQuality(): int {
		return $this->quality;
	}


	/**
	 * @param ImageTransformationInterface $transformation
	 *
	 * @return $this
	 */
	public function addTransformation(ImageTransformationInterface $transformation): self {
		$this->transformations[] = $transformation;
		return $this;
	}

	/**
	 * @return ImageTransformationInterface[]
	 */
	public function getTransformations(): array {
		return $this->transformations;
	}

}
