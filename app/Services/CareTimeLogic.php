<?php

namespace App\Services;

use App\Services\SurveyQuestions;
use App\Services\CareTimeCalculatorInterface;
use App\Services\CareTimeCalculators\MealCareTimeCalculator;
use App\Services\CareTimeCalculators\ExcretionCareTimeCalculator;
use App\Services\CareTimeCalculators\MovementCareTimeCalculator;
use App\Services\CareTimeCalculators\HygieneCareTimeCalculator;
use App\Services\CareTimeCalculators\IndirectCareTimeCalculator;
use App\Services\CareTimeCalculators\BPSDCareTimeCalculator;
use App\Services\CareTimeCalculators\FunctionalTrainingCareTimeCalculator;
use App\Services\CareTimeCalculators\MedicalCareTimeCalculator;

/**
 * 要介護認定基準時間を算出するロジッククラス
 */
class CareTimeLogic
{
    /**
     * 要介護認定基準時間計算クラスの配列
     * 
     * @var array<CareTimeCalculatorInterface>
     */
    private array $calculators;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // 各行為区分の計算クラスを登録
        $this->calculators = [
            new MealCareTimeCalculator(),
            new ExcretionCareTimeCalculator(),
            new MovementCareTimeCalculator(),
            new HygieneCareTimeCalculator(),
            new IndirectCareTimeCalculator(),
            new BPSDCareTimeCalculator(),
            new FunctionalTrainingCareTimeCalculator(),
            new MedicalCareTimeCalculator(),
        ];
    }

    /**
     * 要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return int 要介護認定基準時間（分）
     */
    public function calculateCareTime(array $answers): int
    {
        $totalTime = 0.0;

        // 各計算クラスで時間を算出して合計
        foreach ($this->calculators as $calculator) {
            $totalTime += $calculator->calculate($answers);
        }

        return (int)$totalTime;
    }


    /**
     * 回答内容から中間得点を計算する
     * 
     * @param array $answers 回答データ
     * @return float 中間得点
     */
    public function calculateIntermediateScore(array $answers): float
    {
        $totalScore = 0;

        // 各回答に対する中間得点を計算
        foreach ($answers as $questionId => $answer) {
            $score = SurveyQuestions::getIntermediateScore($questionId, $answer);
            if ($score !== null) {
                $totalScore += $score;
            }
        }

        return $totalScore;
    }

    /**
     * 身体機能・起居動作の中間得点を計算する（第1群）
     * 
     * @param array $answers 回答データ
     * @return float 身体機能・起居動作の中間得点
     */
    public function calculatePhysicalFunctionScore(array $answers): float
    {
        $score = 0;

        // 第1群（1-1から1-13）の質問に対する中間得点を計算
        foreach ($answers as $questionId => $answer) {
            // 質問IDが1-1から1-13の範囲かチェック
            if (preg_match('/^1-([1-9]|1[0-3])$/', $questionId)) {
                $intermediateScore = SurveyQuestions::getIntermediateScore($questionId, $answer);
                if ($intermediateScore !== null) {
                    $score += $intermediateScore;
                }
            }
        }

        return $score;
    }

    /**
     * 生活機能の中間得点を計算する（第2群）
     * 
     * @param array $answers 回答データ
     * @return float 生活機能の中間得点
     */
    public function calculateLifeFunctionScore(array $answers): float
    {
        $score = 0;

        // 第2群（2-1から2-12）の質問に対する中間得点を計算
        foreach ($answers as $questionId => $answer) {
            // 質問IDが2-1から2-12の範囲かチェック
            if (preg_match('/^2-([1-9]|1[0-2])$/', $questionId)) {
                $intermediateScore = SurveyQuestions::getIntermediateScore($questionId, $answer);
                if ($intermediateScore !== null) {
                    $score += $intermediateScore;
                }
            }
        }

        return $score;
    }

    /**
     * 認知機能の中間得点を計算する（第3群）
     * 
     * @param array $answers 回答データ
     * @return float 認知機能の中間得点
     */
    public function calculateCognitiveFunctionScore(array $answers): float
    {
        $score = 0;

        // 第3群（3-1から3-9）の質問に対する中間得点を計算
        foreach ($answers as $questionId => $answer) {
            // 質問IDが3-1から3-9の範囲かチェック
            if (preg_match('/^3-[1-9]$/', $questionId)) {
                $intermediateScore = SurveyQuestions::getIntermediateScore($questionId, $answer);
                if ($intermediateScore !== null) {
                    $score += $intermediateScore;
                }
            }
        }

        return $score;
    }

    /**
     * 精神・行動障害の中間得点を計算する（第4群）
     * 
     * @param array $answers 回答データ
     * @return float 精神・行動障害の中間得点
     */
    public function calculateMentalBehaviorDisorderScore(array $answers): float
    {
        $score = 0;

        // 第4群（4-1から4-15）の質問に対する中間得点を計算
        foreach ($answers as $questionId => $answer) {
            // 質問IDが4-1から4-15の範囲かチェック
            if (preg_match('/^4-([1-9]|1[0-5])$/', $questionId)) {
                $intermediateScore = SurveyQuestions::getIntermediateScore($questionId, $answer);
                if ($intermediateScore !== null) {
                    $score += $intermediateScore;
                }
            }
        }

        return $score;
    }

    /**
     * 社会生活への適応の中間得点を計算する（第5群）
     * 
     * @param array $answers 回答データ
     * @return float 社会生活への適応の中間得点
     */
    public function calculateSocialAdaptationScore(array $answers): float
    {
        $score = 0;

        // 第5群（5-1から5-6）の質問に対する中間得点を計算
        foreach ($answers as $questionId => $answer) {
            // 質問IDが5-1から5-6の範囲かチェック
            if (preg_match('/^5-[1-6]$/', $questionId)) {
                $intermediateScore = SurveyQuestions::getIntermediateScore($questionId, $answer);
                if ($intermediateScore !== null) {
                    $score += $intermediateScore;
                }
            }
        }

        return $score;
    }


    /**
     * 要介護度を判定する
     * 
     * @param int $careTime 要介護認定基準時間（分）
     * @return string 要介護度
     */
    public function determineCareLevel(int $careTime): string
    {
        // 要介護認定基準時間に基づいて判定
        // ルールファイルに基づく判定基準
        if ($careTime < 25) {
            return '非該当';
        } elseif ($careTime < 32) {
            return '要支援1';
        } elseif ($careTime < 50) {
            return '要支援2・要介護1';
        } elseif ($careTime < 70) {
            return '要介護2';
        } elseif ($careTime < 90) {
            return '要介護3';
        } elseif ($careTime < 110) {
            return '要介護4';
        } else {
            return '要介護5';
        }
    }
}
