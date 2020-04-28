<?php


namespace OndraKoupil\AppTools\Importing\Reader;


interface ReaderInterface {

	/**
	 * Zahájí čtení, otevře soubor, něco stáhne... zkrátka inicializace
	 * @return void
	 */
	public function startReading();

	/**
	 * Načte další záznam
	 *
	 * @return mixed|null Null = již nelze nic přečíst, konec souboru.
	 */
	public function readNextItem();

	/**
	 * Úklid po čtení
	 *
	 * @return void
	 */
	public function endReading();

	/**
	 * @return mixed
	 */
	public function getCurrentPosition();

}
