<?php

namespace Nubium\SentenceSanitizer;

use Nubium\SentenceScoring\Edge\Search\IFoundWord;
use Nubium\SentenceScoring\Edge\Search\ISearchFactory;

/**
 * Class SentenceScoringSearchProvider.
 */
class SentenceScoringSearchProvider implements ISearchProvider
{
	protected ISearchFactory $searchFactory;

	/** @var array<string> */
	protected array $categories;


	/**
	 * @param array<string> $searchCategories
	 */
	public function __construct(ISearchFactory $searchFactory, array $searchCategories)
	{
		$this->searchFactory = $searchFactory;
		$this->categories = $searchCategories;
	}


	/**
	 * @return IFoundWord[]
	 */
	public function findWordsInSentence(string $sentence): array
	{
		$search = $this->searchFactory->getSearch($sentence);

		// najdeme slova ktore chceme nahradit
		$words = [];
		foreach ($this->categories as $category) {
			// get to the words itself
			$foundWords = $search->findWordsInCategory($category);
			$words = array_merge($foundWords, $words);
		}

		return $words;
	}
}
