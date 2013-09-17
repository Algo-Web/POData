<?php


namespace POData\Writers;


interface IServiceDocumentWriter {

	/**
	 * Write the service document
	 *
	 * @return string
	 */
	public function getOutput();
}