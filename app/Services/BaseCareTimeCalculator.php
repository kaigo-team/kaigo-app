<?php

namespace App\Services;

/**
 * 要介護認定基準時間計算の基底クラス
 * 共通機能（中間得点計算）を提供
 */
abstract class BaseCareTimeCalculator implements CareTimeCalculatorInterface
{
    /**
     * 身体機能・起居動作の中間得点を計算する（第1群）
     * 
     * @param array $answers 回答データ
     * @return float 身体機能・起居動作の中間得点
     */
    protected function calculatePhysicalFunctionScore(array $answers): float
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
    protected function calculateLifeFunctionScore(array $answers): float
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
    protected function calculateCognitiveFunctionScore(array $answers): float
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
    protected function calculateMentalBehaviorDisorderScore(array $answers): float
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
    protected function calculateSocialAdaptationScore(array $answers): float
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
}
