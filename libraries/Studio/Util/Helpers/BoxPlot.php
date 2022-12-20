<?php

namespace Studio\Util\Helpers;

class BoxPlot {

    protected $data = [];
    protected $size = 0;
    protected $low = 0;
    protected $q1 = 0;
    protected $median = 0;
    protected $q3 = 0;
    protected $high = 0;
    protected $sum = 0;
    protected $mean = 0;

    public function __construct($data) {
        if (!array_key_exists(0, $data)) $data = array_values($data);
        if (!count($data)) return;

        sort($data);

        $this->data = $data;
        $this->size = count($data);
        $this->low = min($data);
        $this->q1 = $this->getPercentile($data, 25);
        $this->median = $this->getPercentile($data, 50);
        $this->q3 = $this->getPercentile($data, 75);
        $this->high = max($data);
        $this->sum = array_sum($data);
        $this->mean = $this->sum / count($data);
    }

    /**
     * Returns the lowest value in the data set.
     *
     * @return double|int
     */
    public function getLowest() {
        return $this->low;
    }

    /**
     * Returns the highest value in the data set.
     *
     * @return double|int
     */
    public function getHighest() {
        return $this->high;
    }

    /**
     * Returns the data value at the first quartile.
     *
     * @return double|int
     */
    public function getFirstQuartile() {
        return $this->q1;
    }

    /**
     * Returns the data value at the third quartile.
     *
     * @return double|int
     */
    public function getThirdQuartile() {
        return $this->q3;
    }

    /**
     * Returns the data value at the median.
     *
     * @return double|int
     */
    public function getMedian() {
        return $this->median;
    }

    /**
     * Returns the sum of data.
     *
     * @return double|int
     */
    public function getSum() {
        return $this->sum;
    }

    /**
     * Returns the average of the data set.
     *
     * @return double|int
     */
    public function getMean() {
        return $this->mean;
    }

    /**
     * Returns the number of values in the data set.
     *
     * @return double|int
     */
    public function getLength() {
        return $this->size;
    }

    /**
     * Returns the quartile at which the given value exists in the data set. Between `0` and `1`. Accuracy improves
     * with the size of the data set.
     *
     * @param int|double $value
     * @return double
     */
    public function getPreciseQuartile($value) {
        foreach ($this->data as $i => $v) {
            if ($value <= $v) {
                return $i / $this->size;
            }
        }

        return 1;
    }

    private function getPercentile($data, $percentile) {
        $index = ($percentile / 100) * count($data);

        if (floor($index) === $index) {
            return ($data[($index - 1)] + $data[$index]) / 2;
        }

        return $data[floor($index)];
    }

}
