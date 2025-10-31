<?php

namespace App\Services;

/**
 * 要介護認定基準時間を計算するインターフェース
 */
interface CareTimeCalculatorInterface
{
    /**
     * 要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return float 要介護認定基準時間（分）
     */
    public function calculate(array $answers): float;
}
