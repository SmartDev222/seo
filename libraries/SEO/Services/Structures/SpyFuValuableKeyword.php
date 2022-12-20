<?php

namespace SEO\Services\Structures;

class SpyFuValuableKeyword {

	/**
	 * The two-letter country code this keyword is localized to.
	 *
	 * @var string
	 */
	public $countryCode;

	/**
	 * @var string
	 */
	public $keyword;

	/**
	 * @var string
	 */
	public $topRankedUrl;

	/**
	 * @var int|null
	 */
	public $rank;

	/**
	 * @var int|null
	 */
	public $rankChange;

	/**
	 * @var int|null
	 */
	public $searchVolume;

	/**
	 * @var int|null
	 */
	public $rankingDifficulty;

	/**
	 * @var int|null
	 */
	public $seoClicks;

	/**
	 * @var int|null
	 */
	public $seoClicksChange;

	/**
	 * @var int|null
	 */
	public $totalMonthlyClicks;

	/**
	 * @var int|null
	 */
	public $broadCostPerClick;

	/**
	 * @var int|null
	 */
	public $phraseCostPerClick;

	/**
	 * @var int|null
	 */
	public $exactCostPerClick;

	/**
	 * @var int
	 */
	public $paidCompetitors;

	/**
	 * @var int
	 */
	public $rankingHomepages;

}
