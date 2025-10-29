<?php

namespace App\Services;

use App\Services\SurveyQuestions;

/**
 * 要介護認定基準時間を算出するロジッククラス
 */
class CareTimeLogic
{
    /**
     * 要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return int 要介護認定基準時間（分）
     */
    public function calculateCareTime(array $answers): int
    {
        // 食事に関する要介護認定基準時間を算出
        $mealTime = $this->calculateMealTime($answers);

        // 他の行為区分の時間も今後実装予定
        // 現在は食事の時間のみを返す
        return (int)$mealTime;
    }

    /**
     * 食事に関する要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return float 食事に関する時間（分）
     */
    public function calculateMealTime(array $answers): float
    {
        // 食事に関する樹形モデルの実装
        // 時間の表示範囲：1.1～71.4分

        // 項目2-4食事摂取の回答をチェック
        if (!isset($answers['2-4'])) {
            return 1.1; // デフォルト値
        }

        $mealAnswer = $answers['2-4'];

        // 食事摂取が「自立（介助なし）」または「見守り等」の場合
        if ($mealAnswer === '自立（介助なし）' || $mealAnswer === '見守り等') {
            return $this->calculateMealTimeForIndependent($answers);
        }
        // 食事摂取が「一部介助」または「全介助」の場合
        elseif ($mealAnswer === '一部介助' || $mealAnswer === '全介助') {
            return $this->calculateMealTimeForAssisted($answers);
        }

        return 1.1; // デフォルト値
    }

    /**
     * 自立・見守り等の場合の食事時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 食事時間（分）
     */
    private function calculateMealTimeForIndependent(array $answers): float
    {
        // 生活機能の中間得点を計算
        $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

        if ($lifeFunctionScore <= 31.2) {
            // 認知機能の中間得点を計算
            $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

            if ($cognitiveScore <= 40.3) {
                return 18.6;
            } else {
                // 精神・行動障害の中間得点を計算
                $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                if ($mentalScore <= 88.9) {
                    return 13.7;
                } else {
                    return 11.2;
                }
            }
        } else {
            // 項目2-2移動の回答をチェック
            if (!isset($answers['2-2'])) {
                return 1.1;
            }

            $movementAnswer = $answers['2-2'];

            if ($movementAnswer === '自立（介助なし）' || $movementAnswer === '見守り等') {
                // 項目3-4短期記憶の回答をチェック
                if (!isset($answers['3-4'])) {
                    return 1.1;
                }

                if ($answers['3-4'] === 'できる') {
                    return 3.4;
                } else {
                    // 項目2-12外出頻度の回答をチェック
                    if (!isset($answers['2-12'])) {
                        return 1.1;
                    }

                    $outingAnswer = $answers['2-12'];

                    if ($outingAnswer === '週1回以上' || $outingAnswer === '月1回以上') {
                        return 10.1;
                    } else {
                        if ($lifeFunctionScore <= 48.6) {
                            return 8.8;
                        } else {
                            return 5.0;
                        }
                    }
                }
            } else {
                // 項目3-4短期記憶の回答をチェック
                if (!isset($answers['3-4'])) {
                    return 1.1;
                }

                if ($answers['3-4'] === 'できる') {
                    return 6.8;
                } else {
                    // 身体機能・起居動作の中間得点を計算
                    $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                    if ($physicalScore <= 67.1) {
                        return 11.1;
                    } else {
                        return 7.5;
                    }
                }
            }
        }
    }

    /**
     * 一部介助・全介助の場合の食事時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 食事時間（分）
     */
    private function calculateMealTimeForAssisted(array $answers): float
    {
        // 項目2-3嚥下の回答をチェック
        if (!isset($answers['2-3'])) {
            return 1.1;
        }

        $swallowingAnswer = $answers['2-3'];

        if ($swallowingAnswer === 'できる' || $swallowingAnswer === '見守り等') {
            // 生活機能の中間得点を計算
            $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

            if ($lifeFunctionScore <= 11.5) {
                // 認知機能の中間得点を計算
                $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

                if ($cognitiveScore <= 27.7) {
                    // 身体機能・起居動作の中間得点を計算
                    $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                    if ($physicalScore <= 12.3) {
                        return 71.4;
                    } else {
                        // 項目1-12視力の回答をチェック
                        if (!isset($answers['1-12'])) {
                            return 1.1;
                        }

                        $visionAnswer = $answers['1-12'];

                        if ($visionAnswer === '普通（日常生活に支障がない）') {
                            return 65.9;
                        } else {
                            return 56.0;
                        }
                    }
                } else {
                    return 45.4;
                }
            } else {
                // 項目2-7口腔清潔の回答をチェック
                if (!isset($answers['2-7'])) {
                    return 1.1;
                }

                $oralCareAnswer = $answers['2-7'];

                if ($oralCareAnswer === '自立（介助なし）' || $oralCareAnswer === '一部介助') {
                    if ($lifeFunctionScore >= 35.4) {
                        return 21.6;
                    } else {
                        return 15.4;
                    }
                } else {
                    // 項目1-3寝返りの回答をチェック
                    if (!isset($answers['1-3'])) {
                        return 1.1;
                    }

                    $turningAnswer = $answers['1-3'];

                    if ($turningAnswer === 'つかまらないでできる' || $turningAnswer === '何かにつかまればできる') {
                        // 項目1-1麻痺の回答をチェック
                        if (!isset($answers['1-1'])) {
                            return 1.1;
                        }

                        $paralysisAnswer = $answers['1-1'];

                        if (empty($paralysisAnswer) || (is_array($paralysisAnswer) && empty($paralysisAnswer))) {
                            return 34.2;
                        } else {
                            return 25.3;
                        }
                    } else {
                        // 寝返りができない場合の処理（ロジックが未定義のためデフォルト値を返す）
                        return 1.1;
                    }
                }
            }
        } else {
            // 嚥下ができない場合
            return 1.1;
        }
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
        if ($careTime < 32) {
            return '自立';
        } elseif ($careTime < 50) {
            return '要支援1';
        } elseif ($careTime < 70) {
            return '要支援2';
        } elseif ($careTime < 90) {
            return '要介護1';
        } elseif ($careTime < 110) {
            return '要介護2';
        } elseif ($careTime < 130) {
            return '要介護3';
        } elseif ($careTime < 150) {
            return '要介護4';
        } else {
            return '要介護5';
        }
    }
}
