<?php

namespace Nubium\SentenceSanitizer\Tests;

use Mockery;
use Nubium\SentenceScoring\Edge\ISentenceScoringService;
use Nubium\SentenceScoring\Edge\Search\IFoundWord;
use Nubium\SentenceScoring\Edge\Search\ISearch;
use Nubium\SentenceScoring\Edge\Search\ISearchFactory;
use Nubium\SentenceSanitizer\BadWordsMaskingSanitizer;
use Nubium\SentenceSanitizer\SentenceScoringSearchProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class MaskingSanitizerTest.
 */
class MaskingSanitizerTest extends TestCase
{
	/**
	 * @param array<mixed> $foundWords
	 *
	 * @dataProvider sanitizeDataProvider
	 */
	public function testSanitize(string $sentence, array $foundWords, string $expectedResult, string $replacement): void
	{
		$search = Mockery::mock(ISearch::class);

		$words = [];
		foreach ($foundWords as $foundWord) {
			$word = Mockery::mock(IFoundWord::class);
			$word->shouldReceive('getWord')->andReturn($foundWord[0]);
			$word->shouldReceive('getMatchType')->andReturn($foundWord[1]);

			$words[] = $word;
		}

		$search->shouldReceive('findWordsInCategory')->with(ISentenceScoringService::CATEGORY_3)->andReturn($words);

		/** @var ISearchFactory $searchFactory */
		$searchFactory = Mockery::mock(ISearchFactory::class)
			->shouldReceive('getSearch')->with($sentence)->andReturn($search)->getMock();

		$searchProvider = new SentenceScoringSearchProvider($searchFactory, [ISentenceScoringService::CATEGORY_3]);

		$sanitizer = new BadWordsMaskingSanitizer($searchProvider, $replacement);

		$result = $sanitizer->sanitize($sentence);

		$this->assertSame($expectedResult, $result, 'Incorrect sentence after sanitizing.');
	}


	/**
	 * @return array<mixed>
	 */
	public function sanitizeDataProvider(): array
	{
		$data = [];
		foreach (['***', ''] as $replacement) {
			foreach ($this->getSanitizeData($replacement) as $sanitizeData) {
				$sanitizeData[] = $replacement;
				$data[] = $sanitizeData;
			}
		}

		return $data;
	}


	/**
	 * @return array<mixed>
	 */
	private function getSanitizeData(string $replacement): array
	{
		return [
			'normal' => [
				'sentence' => 'lorem ipsum sit amet',
				'foundWords' => [],
				'result' => 'lorem ipsum sit amet',
			],
			'simple profanity' => [
				'sentence' => 'lorem ipsum kôkot sit kokot amet',
				'foundWords' => [
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
				],
				'result' => "lorem ipsum $replacement sit $replacement amet",
			],
			'multiword profanity' => [
				'sentence' => 'lorem changing room ipsum kôkot sit kokot amet',
				'foundWords' => [
					['changing room', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
				],
				'result' => "lorem $replacement ipsum $replacement sit $replacement amet",
			],
			'substring profanity' => [
				'sentence' => 'lorem changing room ipsum pokôkote bondagecore sit kokotisko amet',
				'foundWords' => [
					['changing room', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['bondage', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
				],
				'result' => "lorem $replacement ipsum $replacement $replacement sit $replacement amet",
			],
			'nonword znaky' => [
				'sentence' => 'lorem changing room ipsum ~pokôkote-- bondagecore(22) sit ??kokotisko! amet',
				'foundWords' => [
					['changing room', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['bondage', ISentenceScoringService::MATCHTYPE_SUBSTRING],
				],
				'result' => "lorem $replacement ipsum ~$replacement-- $replacement(22) sit ??$replacement! amet",
			],
			'nonword znaky medzi pismenami' => [
				'sentence' => 'lorem changing room ipsum ~p-o-k-ô-k-o-t-e-- bondag~~ecore(22) sit ??kokotisko! amet',
				'foundWords' => [
					['changing room', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['bondage', ISentenceScoringService::MATCHTYPE_SUBSTRING],
				],
				'result' => "lorem $replacement ipsum ~p-o-k-ô-k-o-t-e-- bondag~~ecore(22) sit ??$replacement! amet",
			],
			'use case ze storky' => [
				'sentence' => 'blink 182- i wanna fuck dog in the ass.mp3',
				'foundWords' => [
					['fuck', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['ass', ISentenceScoringService::MATCHTYPE_FULLMATCH],
				],
				'result' => "blink 182- i wanna $replacement dog in the $replacement.mp3",
			],
			'fullmatch profanity' => [
				'sentence' => 'lorem changing room ipsum kôkot grape rape sit kokot amet',
				'foundWords' => [
					['changing room', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['rape', ISentenceScoringService::MATCHTYPE_FULLMATCH],
				],
				'result' => "lorem $replacement ipsum $replacement grape $replacement sit $replacement amet",
			],
			'long description test' => [
				'sentence' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec kôkotík pedophilia pervers perverz preteen preversion prevert fekal horsefuck incest vyprca vyprstena vysousta odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam nec ante. Sed lacinia, urna non tincidunt mattis, tortor neque adipiscing diam, a cursus ipsum ante quis turpis. Nulla facilisi. Ut fringilla. Suspendisse potenti. Nunc feugiat mi a tellus consequat imperdiet. Vestibulum sapien. Proin quam. Etiam ultrices. Suspendisse in justo eu magna luctus suscipit. Sed lectus. Integer euismod lacus luctus magna. Quisque cursus, metus vitae pharetra auctor, sem massa mattis sem, at interdum magna augue eget diam. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi lacinia molestie dui. Praesent blandit dolor. Sed non quam. In vel mi sit amet augue congue elementum. Morbi in ipsum sit amet pede facilisis laoreet. Donec lacus nunc, viverra nec, blandit vel, egestas et, augue. Vestibulum tincidunt malesuada tellus. Ut ultrices ultrices enim. Curabitur sit amet mauris. Morbi in dui quis est pulvinar ullamcorper. Nulla facilisi. Integer lacinia sollicitudin massa. Cras metus. Sed aliquet risus a tortor. Integer id quam. Morbi mi. Quisque nisl felis, venenatis tristique, dignissim in, ultrices sit amet, augue. Proin sodales libero eget ante. Nulla quam. Aenean laoreet. Vestibulum nisi lectus, commodo ac, facilisis ac, ultricies eu, pede. Ut orci risus, accumsan porttitor, cursus quis, aliquet eget, justo. Sed pretium blandit orci. Ut eu diam at pede suscipit sodales. Aenean lectus elit, fermentum non, convallis id, sagittis at, neque. Nullam mauris orci, aliquet et, iaculis et, viverra vitae, ligula. Nulla ut felis in purus aliquam imperdiet. Maecenas aliquet mollis lectus. Vivamus consectetuer risus et tortor. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam nec ante. Sed lacinia, urna non tincidunt mattis, tortor neque adipiscing diam, a cursus ipsum ante quis turpis. Nulla facilisi. Ut fringilla. Suspendisse potenti. Nunc feugiat mi a tellus consequat imperdiet. Vestibulum sapien. Proin quam. Etiam ultrices. Suspendisse in justo eu magna luctus suscipit. Sed lectus. Integer euismod lacus luctus magna. Quisque cursus, metus vitae pharetra auctor, sem massa mattis sem, at interdum magna augue eget diam. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi lacinia molestie dui. Praesent blandit dolor. Sed non quam. In vel mi sit amet augue congue elementum. Morbi in ipsum sit amet pede facilisis laoreet. Donec lacus nunc, viverra nec, blandit vel, egestas et, augue. Vestibulum tincidunt malesuada tellus. Ut ultrices ultrices enim. Curabitur sit amet mauris. Morbi in dui quis est pulvinar ullamcorper. Nulla facilisi. Integer lacinia sollicitudin massa. Cras metus. Sed aliquet risus a tortor. Integer id quam. Morbi mi. Quisque nisl felis, venenatis tristique, dignissim in, ultrices sit amet, augue. Proin sodales libero eget ante. Nulla quam. Aenean laoreet. Vestibulum nisi lectus, commodo ac, facilisis ac, ultricies eu, pede. Ut orci risus, accumsan porttitor, cursus quis, aliquet eget, justo. Sed pretium blandit orci. Ut eu diam at pede suscipit sodales. Aenean lectus elit, fermentum non, convallis id, sagittis at, neque. Nullam mauris orci, aliquet et, iaculis et, viverra vitae, ligula. Nulla ut felis in purus aliquam imperdiet. Maecenas aliquet mollis lectus. Vivamus consectetuer risus et tortor. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam nec ante. Sed lacinia, urna non tincidunt mattis, tortor neque adipiscing diam, a cursus ipsum ante quis turpis. Nulla facilisi. Ut fringilla. Suspendisse potenti. Nunc feugiat mi a tellus consequat imperdiet. Vestibulum sapien. Proin quam. Etiam ultrices. Suspendisse in justo eu magna luctus suscipit. Sed lectus. Integer euismod lacus luctus magna. Quisque cursus, metus vitae pharetra auctor, sem massa mattis sem, at interdum magna augue eget diam. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi lacinia molestie dui. Praesent blandit dolor. Sed non quam. In vel mi sit amet augue kokotisko congue elementum. Morbi in ipsum si.',
				'foundWords' => [
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['pedophilia', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['pervers', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['perverz', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['preteen', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['preversion', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['prevert', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['fekal', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['horsefuck', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['incest', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['vyprca', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['vyprstena', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['vysousta', ISentenceScoringService::MATCHTYPE_FULLMATCH],
				],
				'result' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec $replacement $replacement $replacement $replacement $replacement $replacement $replacement $replacement $replacement $replacement $replacement $replacement $replacement odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam nec ante. Sed lacinia, urna non tincidunt mattis, tortor neque adipiscing diam, a cursus ipsum ante quis turpis. Nulla facilisi. Ut fringilla. Suspendisse potenti. Nunc feugiat mi a tellus consequat imperdiet. Vestibulum sapien. Proin quam. Etiam ultrices. Suspendisse in justo eu magna luctus suscipit. Sed lectus. Integer euismod lacus luctus magna. Quisque cursus, metus vitae pharetra auctor, sem massa mattis sem, at interdum magna augue eget diam. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi lacinia molestie dui. Praesent blandit dolor. Sed non quam. In vel mi sit amet augue congue elementum. Morbi in ipsum sit amet pede facilisis laoreet. Donec lacus nunc, viverra nec, blandit vel, egestas et, augue. Vestibulum tincidunt malesuada tellus. Ut ultrices ultrices enim. Curabitur sit amet mauris. Morbi in dui quis est pulvinar ullamcorper. Nulla facilisi. Integer lacinia sollicitudin massa. Cras metus. Sed aliquet risus a tortor. Integer id quam. Morbi mi. Quisque nisl felis, venenatis tristique, dignissim in, ultrices sit amet, augue. Proin sodales libero eget ante. Nulla quam. Aenean laoreet. Vestibulum nisi lectus, commodo ac, facilisis ac, ultricies eu, pede. Ut orci risus, accumsan porttitor, cursus quis, aliquet eget, justo. Sed pretium blandit orci. Ut eu diam at pede suscipit sodales. Aenean lectus elit, fermentum non, convallis id, sagittis at, neque. Nullam mauris orci, aliquet et, iaculis et, viverra vitae, ligula. Nulla ut felis in purus aliquam imperdiet. Maecenas aliquet mollis lectus. Vivamus consectetuer risus et tortor. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam nec ante. Sed lacinia, urna non tincidunt mattis, tortor neque adipiscing diam, a cursus ipsum ante quis turpis. Nulla facilisi. Ut fringilla. Suspendisse potenti. Nunc feugiat mi a tellus consequat imperdiet. Vestibulum sapien. Proin quam. Etiam ultrices. Suspendisse in justo eu magna luctus suscipit. Sed lectus. Integer euismod lacus luctus magna. Quisque cursus, metus vitae pharetra auctor, sem massa mattis sem, at interdum magna augue eget diam. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi lacinia molestie dui. Praesent blandit dolor. Sed non quam. In vel mi sit amet augue congue elementum. Morbi in ipsum sit amet pede facilisis laoreet. Donec lacus nunc, viverra nec, blandit vel, egestas et, augue. Vestibulum tincidunt malesuada tellus. Ut ultrices ultrices enim. Curabitur sit amet mauris. Morbi in dui quis est pulvinar ullamcorper. Nulla facilisi. Integer lacinia sollicitudin massa. Cras metus. Sed aliquet risus a tortor. Integer id quam. Morbi mi. Quisque nisl felis, venenatis tristique, dignissim in, ultrices sit amet, augue. Proin sodales libero eget ante. Nulla quam. Aenean laoreet. Vestibulum nisi lectus, commodo ac, facilisis ac, ultricies eu, pede. Ut orci risus, accumsan porttitor, cursus quis, aliquet eget, justo. Sed pretium blandit orci. Ut eu diam at pede suscipit sodales. Aenean lectus elit, fermentum non, convallis id, sagittis at, neque. Nullam mauris orci, aliquet et, iaculis et, viverra vitae, ligula. Nulla ut felis in purus aliquam imperdiet. Maecenas aliquet mollis lectus. Vivamus consectetuer risus et tortor. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam nec ante. Sed lacinia, urna non tincidunt mattis, tortor neque adipiscing diam, a cursus ipsum ante quis turpis. Nulla facilisi. Ut fringilla. Suspendisse potenti. Nunc feugiat mi a tellus consequat imperdiet. Vestibulum sapien. Proin quam. Etiam ultrices. Suspendisse in justo eu magna luctus suscipit. Sed lectus. Integer euismod lacus luctus magna. Quisque cursus, metus vitae pharetra auctor, sem massa mattis sem, at interdum magna augue eget diam. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi lacinia molestie dui. Praesent blandit dolor. Sed non quam. In vel mi sit amet augue $replacement congue elementum. Morbi in ipsum si.",
			],
			'underscores' => [
				'sentence' => 'XXX porno - Bailey Jay (Shemale Pornstar) - clip25 (ayeron 2010)',
				'foundWords' => [
					['xxx', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['porno', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['shemale', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['pornstar', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['ayeron', ISentenceScoringService::MATCHTYPE_FULLMATCH],
				],
				'result' => "{$replacement} {$replacement} - Bailey Jay ({$replacement} {$replacement}) - clip25 ({$replacement} 2010)",
			],
			'subsequent substrings' => [
				'sentence' => 'changing room pokôkote bondagecore',
				'foundWords' => [
					['changing room', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['bondage', ISentenceScoringService::MATCHTYPE_SUBSTRING],
				],
				'result' => "$replacement $replacement $replacement",
			],
			'subsequent fullmatches' => [
				'sentence' => 'kozate koureni kokota',
				'foundWords' => [
					['kozate', ISentenceScoringService::MATCHTYPE_FULLMATCH],
					['kokot', ISentenceScoringService::MATCHTYPE_SUBSTRING],
					['koureni', ISentenceScoringService::MATCHTYPE_FULLMATCH],
				],
				'result' => "$replacement $replacement $replacement",
			],
		];
	}
}
