<?php

namespace Nubium\SentenceSanitizer;

use Nubium\SentenceScoring\Edge\Search\IFoundWord;

interface ISearchProvider
{
	/**
	 * @return IFoundWord[]
	 */
	public function findWordsInSentence(string $sentence): array;
}
