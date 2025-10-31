<?php

namespace App\Services\CareTimeCalculators;

use App\Services\BaseCareTimeCalculator;

/**
 * 排泄に関する要介護認定基準時間を算出するクラス
 * 時間の表示範囲：0.2～28.0分
 */
class ExcretionCareTimeCalculator extends BaseCareTimeCalculator
{
    /**
     * 排泄に関する要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return float 排泄に関する時間（分）
     */
    public function calculate(array $answers): float
    {
        // 生活機能の中間得点を計算
        $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

        if ($lifeFunctionScore <= 65.8) {
            return $this->calculateForLowLifeFunction($answers, $lifeFunctionScore);
        } else {
            return $this->calculateForHighLifeFunction($answers, $lifeFunctionScore);
        }
    }

    /**
     * 生活機能の中間得点が65.8以下の場合の排泄時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 排泄時間（分）
     */
    private function calculateForLowLifeFunction(array $answers, float $lifeFunctionScore): float
    {
        // 項目2-2移動の回答をチェック
        if (!isset($answers['2-2'])) {
            return 0.2; // デフォルト値
        }

        $movementAnswer = $answers['2-2'];

        if ($movementAnswer === '自立（介助なし）' || $movementAnswer === '見守り等') {
            return $this->calculateForIndependentMovement($answers);
        } else {
            // 項目2-2移動が「一部介助」or「全介助」の場合
            return $this->calculateForAssistedMovement($answers, $lifeFunctionScore);
        }
    }

    /**
     * 移動が自立・見守り等の場合の排泄時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 排泄時間（分）
     */
    private function calculateForIndependentMovement(array $answers): float
    {
        // 項目2-6排便の回答をチェック
        if (!isset($answers['2-6'])) {
            return 0.2;
        }

        $bowelMovementAnswer = $answers['2-6'];

        if ($bowelMovementAnswer === '自立（介助なし）' || $bowelMovementAnswer === '見守り等' || $bowelMovementAnswer === '一部介助') {
            // 認知機能の中間得点を計算
            $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

            if ($cognitiveScore <= 58.2) {
                return 15.1;
            } else {
                return 11.6;
            }
        } else {
            // 項目2-6排便が「全介助」の場合
            // 項目2-7口腔清潔の回答をチェック
            if (!isset($answers['2-7'])) {
                return 0.2;
            }

            $oralCareAnswer = $answers['2-7'];

            if ($oralCareAnswer === '自立（介助なし）' || $oralCareAnswer === '一部介助') {
                return 19.1;
            } else {
                // 項目2-7口腔清潔が「全介助」の場合
                return 22.6;
            }
        }
    }

    /**
     * 移動が一部介助・全介助の場合の排泄時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 排泄時間（分）
     */
    private function calculateForAssistedMovement(array $answers, float $lifeFunctionScore): float
    {
        // 精神・行動障害の中間得点を計算
        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

        if ($mentalScore <= 96.4) {
            // 項目5-3日常の意思決定の回答をチェック
            if (!isset($answers['5-3'])) {
                return 0.2;
            }

            $decisionAnswer = $answers['5-3'];

            if ($decisionAnswer === 'できる') {
                return 25.9;
            } else {
                // 項目5-3が「特別な場合を除いてできる」or「日常的に困難」or「できない」の場合
                return $this->calculateForDifficultyDecision($answers, $lifeFunctionScore);
            }
        } else {
            // 精神・行動障害の中間得点が96.5以上の場合
            return $this->calculateForHighMentalScore($answers);
        }
    }

    /**
     * 意思決定が困難な場合の排泄時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 排泄時間（分）
     */
    private function calculateForDifficultyDecision(array $answers, float $lifeFunctionScore): float
    {
        if ($lifeFunctionScore <= 45.5) {
            // 身体機能・起居動作の中間得点を計算
            $physicalScore = $this->calculatePhysicalFunctionScore($answers);

            if ($physicalScore <= 41.3) {
                if ($physicalScore <= 35.3) {
                    if ($lifeFunctionScore <= 20.6) {
                        // 精神・行動障害の中間得点を計算
                        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                        if ($mentalScore <= 77.9) {
                            return 24.5;
                        } else {
                            if ($mentalScore <= 82.9) {
                                return 19.8;
                            } else {
                                if ($lifeFunctionScore <= 3.5) {
                                    return 24.0;
                                } else {
                                    // 項目1-2拘縮の回答をチェック
                                    if (!isset($answers['1-2'])) {
                                        return 21.0;
                                    }

                                    $contractureAnswer = $answers['1-2'];
                                    $hasShoulderContracture = is_array($contractureAnswer) && in_array('肩関節', $contractureAnswer);

                                    if (!$hasShoulderContracture) {
                                        return 21.0;
                                    } else {
                                        // 精神・行動障害の中間得点を計算
                                        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                                        if ($mentalScore <= 91.4) {
                                            return 23.9;
                                        } else {
                                            return 22.1;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        return 25.9;
                    }
                } else {
                    return 20.8;
                }
            } else {
                // 身体機能・起居動作の中間得点が41.4以上の場合
                // 精神・行動障害の中間得点を計算
                $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                if ($mentalScore <= 84.9) {
                    // 項目5-3日常の意思決定の回答をチェック
                    if (!isset($answers['5-3'])) {
                        return 0.2;
                    }

                    $decisionAnswer = $answers['5-3'];

                    if ($decisionAnswer === '特別な場合を除いてできる' || $decisionAnswer === '日常的に困難') {
                        return 24.1;
                    } else {
                        // 項目5-3が「できない」の場合
                        return 28.0;
                    }
                } else {
                    return 22.9;
                }
            }
        } else {
            // 生活機能の中間得点が45.6以上の場合
            return 20.5;
        }
    }

    /**
     * 精神・行動障害の中間得点が高い場合の排泄時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 排泄時間（分）
     */
    private function calculateForHighMentalScore(array $answers): float
    {
        // 身体機能・起居動作の中間得点を計算
        $physicalScore = $this->calculatePhysicalFunctionScore($answers);

        if ($physicalScore <= 11.9) {
            return 22.1;
        } else {
            // 項目1-6両足での立位保持の回答をチェック
            if (!isset($answers['1-6'])) {
                return 0.2;
            }

            $standingAnswer = $answers['1-6'];

            if ($standingAnswer === '支えなしでできる' || $standingAnswer === '何か支えがあればできる') {
                if ($physicalScore <= 55.0) {
                    return 24.5;
                } else {
                    return 20.1;
                }
            } else {
                // 項目1-6が「できない」の場合
                // 社会生活への適応の中間得点を計算
                $socialScore = $this->calculateSocialAdaptationScore($answers);

                if ($socialScore <= 27.0) {
                    // 生活機能の中間得点を計算
                    $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

                    if ($lifeFunctionScore <= 17.5) {
                        // 項目1-2拘縮の回答をチェック
                        if (!isset($answers['1-2'])) {
                            return 21.5;
                        }

                        $contractureAnswer = $answers['1-2'];
                        $hasHipContracture = is_array($contractureAnswer) && in_array('股関節', $contractureAnswer);

                        if (!$hasHipContracture) {
                            return 21.5;
                        } else {
                            // 項目1-12視力の回答をチェック
                            if (!isset($answers['1-12'])) {
                                return 18.4;
                            }

                            $visionAnswer = $answers['1-12'];

                            if ($visionAnswer === '普通（日常生活に支障がない）') {
                                return 19.7;
                            } else {
                                return 18.4;
                            }
                        }
                    } else {
                        return 17.4;
                    }
                } else {
                    return 21.7;
                }
            }
        }
    }

    /**
     * 生活機能の中間得点が65.9以上の場合の排泄時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 排泄時間（分）
     */
    private function calculateForHighLifeFunction(array $answers, float $lifeFunctionScore): float
    {
        // 項目2-6排便の回答をチェック
        if (!isset($answers['2-6'])) {
            return 0.2;
        }

        $bowelMovementAnswer = $answers['2-6'];

        if ($bowelMovementAnswer === '自立（介助なし）' || $bowelMovementAnswer === '見守り等') {
            return $this->calculateForIndependentBowel($answers, $lifeFunctionScore);
        } else {
            // 項目2-6排便が「一部介助」or「全介助」の場合
            return $this->calculateForAssistedBowel($answers, $lifeFunctionScore);
        }
    }

    /**
     * 排便が自立・見守り等の場合の排泄時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 排泄時間（分）
     */
    private function calculateForIndependentBowel(array $answers, float $lifeFunctionScore): float
    {
        if ($lifeFunctionScore <= 87.7) {
            // 項目1-6両足での立位保持の回答をチェック
            if (!isset($answers['1-6'])) {
                return 0.2;
            }

            $standingAnswer = $answers['1-6'];

            if ($standingAnswer === '支えなしでできる') {
                return 2.9;
            } else {
                // 項目1-1麻痺の回答をチェック
                if (!isset($answers['1-1'])) {
                    return 8.2;
                }

                $paralysisAnswer = $answers['1-1'];

                if (empty($paralysisAnswer) || (is_array($paralysisAnswer) && empty($paralysisAnswer))) {
                    return 8.2;
                } else {
                    return 4.7;
                }
            }
        } else {
            // 生活機能の中間得点が87.8以上の場合
            // 身体機能・起居動作の中間得点を計算
            $physicalScore = $this->calculatePhysicalFunctionScore($answers);

            if ($physicalScore <= 85.1) {
                return 2.0;
            } else {
                return 0.2;
            }
        }
    }

    /**
     * 排便が一部介助・全介助の場合の排泄時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 排泄時間（分）
     */
    private function calculateForAssistedBowel(array $answers, float $lifeFunctionScore): float
    {
        // 項目2-11ズボン等の着脱の回答をチェック
        if (!isset($answers['2-11'])) {
            return 0.2;
        }

        $clothingAnswer = $answers['2-11'];

        if ($clothingAnswer === '自立（介助なし）' || $clothingAnswer === '見守り等') {
            return 8.3;
        } else {
            // 項目2-11ズボン等の着脱が「一部介助」or「全介助」の場合
            if ($lifeFunctionScore <= 77.3) {
                return 16.1;
            } else {
                return 11.1;
            }
        }
    }
}
