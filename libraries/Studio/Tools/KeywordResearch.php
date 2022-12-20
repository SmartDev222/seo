<?php

namespace Studio\Tools;

use Exception;
use SEO\Services\Keywords;

class KeywordResearch extends Tool
{
    var $name = "Keyword Research";
    var $id = "keyword-research";
    var $icon = "keyword-research";
    var $template = "keyword-research.html";
    var $requiresWebsite = false;

    var $keyword;
    protected $chosenCountry = null;

    public function prerun($url) {
        $this->keyword = "";

        if (isset($_POST['keyword']) && isset($_POST['country'])) {
            $this->keyword = $_POST['keyword'];
            $this->chosenCountry = trim(strtoupper($_POST['country']));
        }
    }

    public function run() {
        global $studio, $language, $api;

        $countryCode = $this->getSelectedCountry();

        if ($this->keyword != "") {
            $locale = $language ? $language->locale : 'en-us';
            $localeParts = explode('-', strtolower($locale), 2);

            // Fall back for invalid locales
            if (count($localeParts) !== 2) $localeParts = array('en', 'us');

            // Form the query parameters
            $query = mb_strtolower(trim($this->keyword));
            $lang = $localeParts[0];
            $country = $countryCode;

            // Perform the request
            $keywords = Keywords::getKeywordSuggestions($query, $lang, $country);
            $items = array();
            $kwData = array();

            // Get keyword data
            try {
                $returned = Keywords::getKeywordData($keywords, $countryCode);

                foreach($returned as $keyword => $data) {
                    // Add the primary keyword
                    $kwData[mb_strtolower($keyword)] = array(
                        'cpc' => '$' . number_format(floatval($data['cpc']), 2),
                        'vol' => number_format(floatval($data['search_volume'])),
                        'v' => $data['search_volume'],
                        'competition' => floor(100 * 0) . '%'
                    );

                    // Add similar keywords
                    $similarKeywords = [];
                    foreach ($data['similar_keywords'] as $similar) {
                        $similarKeywords[] = $similar['keyword'];
                        $kwData[mb_strtolower($similar['keyword'])] = array(
                            'cpc' => '$' . number_format(floatval($similar['cpc']), 2),
                            'vol' => number_format(floatval($similar['search_volume'])),
                            'v' => $similar['search_volume'],
                            'competition' => floor(100 * 0) . '%'
                        );
                    }
                }
            }
            catch (Exception $e) {
            }

            // Then, prepare the rows to render
            foreach ($kwData as $keyword => $value) {
                $items[] = array(
                    'cpc' => $value['cpc'],
                    'text' => $keyword,
                    'vol' => $value['vol'],
                    'comp' => $value['competition'],
                    'v' => $value['v']
                );
            }

            // Sort rows by volume
            usort($items, function($a, $b) use ($query) {
                // Always show the original query on top
                if ($a['text'] === $query) return -1;
                if ($b['text'] === $query) return 1;

                // Sort the rest by volume, descending
                if ($a['v'] == $b['v']) return 0;
                return $a['v'] < $b['v'] ? 1 : -1;
            });

            $this->data = $items;
        }
    }

    public function output() {
        echo $this->renderTemplate([
            'keyword' => $this->keyword,
            'results' => $this->data,
            'countries' => $this->getCountries(),
            'activeCountry' => $this->getSelectedCountry()
        ]);

        return;

        $html = $this->getTemplate();
        $html = str_replace("[[KEYWORD]]", $this->keyword, $html);

        if (is_array($this->data) && count($this->data) > 0) {
            $itemsHTML = "";
            foreach ($this->data as $i => $query) {
                $odd = (($i % 2 == 0) ? "" : "odd");
                $itemsHTML .= "<tr class=\"$odd\">
                    <td>{$query['text']}</td>
                    <td class=\"center\">{$query['cpc']}</td>
                    <td class=\"center\">{$query['vol']}</td>
                </tr>";
            }
        }
        else {
            $itemsHTML = "<tr>
                <td colspan=\"4\">" . rt("Nothing to show.") . "</td>
            </tr>";
        }

        $countries = $language->getCountries();
        $activeCountry = $this->getSelectedCountry();
        $supported = ["ae","ar","at","au","bd","be","bg","bo","br","ca","ch","cl","co","cr","cz","de","dk","dz","ec","ee","eg","es","fi","fr","gb","gr","gt","hk","hr","hu","id","ie","il","in","it","jp","lk","mx","my","ng","ni","nl","no","nz","pe","pl","pt","py","ro","rs","ru","sa","se","sg","si","sk","sv","th","tn","tr","tw","ua","us","uy","ve","vn","za"];

        if (!in_array(strtolower($activeCountry), $supported)) {
            $activeCountry = 'US';
        }

        $countriesHTML = '';
        foreach ($countries as $code => $name) {
            if (is_array($name)) $name = $name[0];
            if (!in_array(strtolower($code), $supported)) continue;

            $selected = $code == $activeCountry ? 'selected' : '';
            $countriesHTML .= ("
                <option ${selected} value='{$code}'>{$name}</option>
            ");
        }

        $html = str_replace('[[COUNTRIES]]', $countriesHTML, $html);
        $html = str_replace("[[ITEMS]]", $itemsHTML, $html);

        echo sanitize_trusted($html);
    }

    private function getCountries() {
        global $language;

        $countries = $language->getCountries();
        $supported = ["ae","ar","at","au","bd","be","bg","bo","br","ca","ch","cl","co","cr","cz","de","dk","dz","ec","ee","eg","es","fi","fr","gb","gr","gt","hk","hr","hu","id","ie","il","in","it","jp","lk","mx","my","ng","ni","nl","no","nz","pe","pl","pt","py","ro","rs","ru","sa","se","sg","si","sk","sv","th","tn","tr","tw","ua","us","uy","ve","vn","za"];
        $results = [];

        foreach ($countries as $code => $name) {
            if (is_array($name)) $name = $name[0];
            if (!in_array(strtolower($code), $supported)) continue;

            $results[$code] = $name;
        }

        return $results;
    }

    public function record($data = "") {
        if ($this->keyword != "") parent::record("Keyword: " . $this->keyword);
    }

    protected function getCacheKey() {
        return $this->id . ":" . $this->keyword . ":" . $this->getSelectedCountry();
    }

    /**
     * Returns the country to select in the dropdown.
     */
    private function getSelectedCountry() {
        global $studio;

        if (!is_null($this->chosenCountry)) {
            return $this->chosenCountry;
        }

        if (isset($_SESSION['kwr_country'])) {
            return $_SESSION['kwr_country'];
        }

        return $studio->getopt('tools-default-country', 'US');
    }

}
