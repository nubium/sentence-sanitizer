<?php

namespace Nubium\SentenceSanitizer;

use Nubium\SentenceScoring\Edge\ISentenceScoringService;
use Nubium\SentenceScoring\Edge\Search\IFoundWord;

/**
 * Class BadWordsMaskingSanitizer.
 */
class BadWordsMaskingSanitizer implements ISentenceSanitizer
{
	protected ISearchProvider $searchProvider;
	protected string $replacement;

	/**
	 * @var array<string,string>
	 */
	private array $translationTable = [
		'a' => '[AaÁĂẮẶẰẲẴǍÂẤẬẦẨẪÄǞȦǠẠȀÀẢȂĀĄÅǺḀȺÃⱯᴀáăắặằẳẵǎâấậầẩẫäǟȧǡạȁàảȃāąᶏẚåǻḁⱥãɐₐ]',
		'b' => '[BbḂḄƁḆɃƂʙᴃḃḅɓḇᵬᶀƀƃ]',
		'c' => '[CcĆČÇḈĈĊƇȻꜾᴄćčçḉĉɕċƈȼↄꜿ]',
		'd' => '[DdĎḐḒḊḌƊḎǲǅĐƋꝹᴅďḑḓȡḋḍɗᶑḏᵭᶁđɖƌꝺ]',
		'e' => '[EeÉĔĚȨḜÊẾỆỀỂỄḘËĖẸȄÈẺȆĒḖḔĘɆẼḚƐƎᴇⱻéĕěȩḝêếệềểễḙëėẹȅèẻȇēḗḕⱸęᶒɇẽḛɛᶓɘǝₑ]',
		'f' => '[FfḞƑꝻꜰḟƒᵮᶂꝼ]',
		'g' => '[GgǴĞǦĢĜĠƓḠǤꝽɢʛǵğǧģĝġɠḡᶃǥᵹɡᵷ]',
		'h' => '[HhḪȞḨĤⱧḦḢḤĦʜḫȟḩĥⱨḧḣḥɦẖħɥʮʯ]',
		'i' => '[IiÍĬǏÎÏḮİỊȈÌỈȊĪĮƗĨḬɪıíĭǐîïḯịȉìỉȋīįᶖɨĩḭᴉᵢ]',
		'r' => '[RrꞂŔŘŖṘṚṜȐȒṞɌⱤʁʀᴙᴚꞃŕřŗṙṛṝȑɾᵳȓṟɼᵲᶉɍɽɿɹɻɺⱹᵣ]',
		's' => '[SsꞄŚṤŠṦŞŜȘṠṢṨꜱꞅſẜẛẝśṥšṧşŝșṡṣṩʂᵴᶊȿ]',
		't' => '[TtꞆŤŢṰȚȾṪṬƬṮƮŦᴛꞇťţṱțȶẗⱦṫṭƭṯᵵƫʈŧʇ]',
		'j' => '[JjĴɈᴊȷɟʄǰĵʝɉⱼ]',
		'k' => '[KkḰǨĶⱩꝂḲƘḴꝀꝄᴋḱǩķⱪꝃḳƙḵᶄꝁꝅʞ]',
		'l' => '[LlĹȽĽĻḼḶḸⱠꝈḺĿⱢǈŁꞀʟᴌĺƚɬľļḽȴḷḹⱡꝉḻŀɫᶅɭłꞁ]',
		'm' => '[MmḾṀṂⱮƜᴍḿṁṃɱᵯᶆɯɰ]',
		'n' => '[NnŃŇŅṊṄṆǸƝṈȠǋÑɴᴎńňņṋȵṅṇǹɲṉƞᵰᶇɳñ]',
		'o' => '[OoÓŎǑÔỐỘỒỔỖÖȪȮȰỌŐȌÒỎƠỚỢỜỞỠȎꝊꝌŌṒṐƟǪǬØǾÕṌṎȬƆᴏᴐɵóŏǒôốộồổỗöȫȯȱọőȍòỏơớợờởỡȏꝋꝍⱺōṓṑǫǭøǿõṍṏȭɔᶗᴑᴓₒóô]',
		'p' => '[PpṔṖꝒƤꝔⱣꝐᴘṕṗꝓƥᵱᶈꝕᵽꝑ]',
		'q' => '[QqꝘꝖꝙʠɋꝗ]',
		'v' => '[VvɅꝞṾƲṼᴠʌⱴꝟṿʋᶌⱱṽᵥ]',
		'u' => '[UuÚŬǓÛṶÜǗǙǛǕṲỤŰȔÙỦƯỨỰỪỬỮȖŪṺŲŮŨṸṴᴜᴝúŭǔûṷüǘǚǜǖṳụűȕùủưứựừửữȗūṻųᶙůũṹṵᵤ]',
		'w' => '[WwẂŴẄẆẈẀⱲᴡʍẃŵẅẇẉẁⱳẘ]',
		'x' => '[XxẌẊẍẋᶍₓ]',
		'y' => '[YyÝŶŸẎỴỲƳỶỾȲɎỸʏʎýŷẏỵỳƴỷỿȳẙɏỹ]',
		'z' => '[ZzŹŽẐⱫŻẒȤẔƵᴢźžẑʑⱬżẓȥẕᵶᶎʐƶɀ]',
		' ' => '(\W)+',
	];


	public function __construct(ISearchProvider $searchProvider, string $replacement = '***')
	{
		$this->searchProvider = $searchProvider;
		$this->replacement = $replacement;
	}


	public function sanitize(?string $sentence): string
	{
		$words = $this->searchProvider->findWordsInSentence($sentence);

		// fullmatch slova
		if ($regexpFullmatch = $this->prepareFullmatchRegexp($words)) {
			$sentence = preg_replace($regexpFullmatch, $this->replacement, $sentence);
		}

		// substring slova
		if ($regexpSubstring = $this->prepareSubstringRegexp($words)) {
			$sentence = preg_replace($regexpSubstring, $this->replacement, $sentence);
		}

		return $sentence;
	}


	/**
	 * Vytvori regexp na matchnutie FULLMATCH wordov.
	 *
	 * @param IFoundWord[] $words
	 */
	protected function prepareFullmatchRegexp(array $words): ?string
	{
		$wordMatches = [];
		foreach ($words as $word) {
			// ak to slovo nie je fullmatch alebo je z nejakeho dovodu prazdne
			if (!strlen($word->getWord()) || $word->getMatchType() != ISentenceScoringService::MATCHTYPE_FULLMATCH) {
				continue;
			}

			$letterMatches = [];
			// vsetky zname alternativy pre kazde pismeno
			foreach (str_split($word->getWord()) as $letter) {
				$letterMatches[] = $this->translateLetterToRegexp($letter);
			}

			// a pridame regexp do zoznamu
			$wordMatches[$word->getWord()] = implode($letterMatches);
		}

		// ak nam vysiel prazdny regexp, kaslime na to
		if (!count($wordMatches)) {
			return null;
		}

		// zlozime vysledny zoznam regexpov do jedneho
		$regexp = '/(?<=[^\p{L}\p{N}]|^)(' . implode('|', $wordMatches) . ')(?=[^\p{L}\p{N}]|$)/u';

		return $regexp;
	}


	/**
	 * Vytvori regexp na matchnutie SUBSTRING wordov.
	 *
	 * @param IFoundWord[] $words
	 */
	protected function prepareSubstringRegexp(array $words): ?string
	{
		$wordMatches = [];
		foreach ($words as $word) {
			// ak to slovo nie je fullmatch alebo je z nejakeho dovodu prazdne
			if (!strlen($word->getWord()) || $word->getMatchType() != ISentenceScoringService::MATCHTYPE_SUBSTRING) {
				continue;
			}

			$letterMatches = [];
			// vsetky zname alternativy pre kazde pismeno
			foreach (str_split($word->getWord()) as $letter) {
				$letterMatches[] = $this->translateLetterToRegexp($letter);
			}

			// a pridame regexp do zoznamu
			$wordMatches[$word->getWord()] = implode($letterMatches);
		}

		// ak nam vysiel prazdny regexp, kaslime na to
		if (!count($wordMatches)) {
			return null;
		}

		// zlozime vysledny zoznam regexpov do jedneho
		$regexp = '/([\p{L}\p{N}])*(' . implode('|', $wordMatches) . ')([\p{L}\p{N}])*/u';

		return $regexp;
	}


	protected function translateLetterToRegexp(string $letter): string
	{
		return isset($this->translationTable[$letter]) ? $this->translationTable[$letter] : '';
	}
}
