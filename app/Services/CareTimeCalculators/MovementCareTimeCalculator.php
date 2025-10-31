<?php

namespace App\Services\CareTimeCalculators;

use App\Services\BaseCareTimeCalculator;

/**
 * 移動に関する要介護認定基準時間を算出するクラス
 * 時間の表示範囲：0.4～21.4分
 */
class MovementCareTimeCalculator extends BaseCareTimeCalculator
{
    /**
     * 移動に関する要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return float 移動に関する時間（分）
     */
    public function calculate(array $answers): float
    {
        // 生活機能の中間得点を計算
        $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

        if ($lifeFunctionScore <= 63.2) {
            return $this->calculateForLowLifeFunction($answers, $lifeFunctionScore);
        } else {
            return $this->calculateForHighLifeFunction($answers, $lifeFunctionScore);
        }
    }

    /**
     * 生活機能の中間得点が63.2以下の場合の移動時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 移動時間（分）
     */
    private function calculateForLowLifeFunction(array $answers, float $lifeFunctionScore): float
    {
        if ($lifeFunctionScore <= 3.4) {
            // 精神・行動障害の中間得点を計算
            $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

            if ($mentalScore <= 97.6) {
                return 11.4;
            } else {
                // 項目1-13聴力の回答をチェック
                if (!isset($answers['1-13'])) {
                    return 0.4;
                }

                $hearingAnswer = $answers['1-13'];

                if ($hearingAnswer === '普通' || $hearingAnswer === '普通の声がやっと聴き取れる') {
                    return 10.4;
                } else {
                    // 身体機能・起居動作の中間得点を計算
                    $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                    if ($physicalScore <= 1.6) {
                        return 8.8;
                    } else {
                        return 7.3;
                    }
                }
            }
        } else {
            // 生活機能の中間得点が3.5以上の場合
            // 項目2-2移動の回答をチェック
            if (!isset($answers['2-2'])) {
                return 0.4;
            }

            $movementAnswer = $answers['2-2'];

            if ($movementAnswer === '自立（介助なし）' || $movementAnswer === '見守り等') {
                if ($lifeFunctionScore <= 43.7) {
                    return 14.6;
                } else {
                    // 項目5-3日常の意思決定の回答をチェック
                    if (!isset($answers['5-3'])) {
                        return 0.4;
                    }

                    $decisionAnswer = $answers['5-3'];

                    if ($decisionAnswer === 'できる' || $decisionAnswer === '特別な場合を除いてできる') {
                        // 項目2-1移乗の回答をチェック
                        if (!isset($answers['2-1'])) {
                            return 0.4;
                        }

                        $transferAnswer = $answers['2-1'];

                        if ($transferAnswer === '自立（介助なし）' || $transferAnswer === '見守り等') {
                            return 7.6;
                        } else {
                            return 11.1;
                        }
                    } else {
                        // 項目5-3が「日常的に困難」or「できない」の場合
                        return 12.6;
                    }
                }
            } else {
                // 項目2-2移動が「一部介助」or「全介助」の場合
                // 社会生活への適応の中間得点を計算
                $socialScore = $this->calculateSocialAdaptationScore($answers);

                if ($socialScore <= 3.6) {
                    // 認知機能の中間得点を計算
                    $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

                    if ($cognitiveScore <= 19.7) {
                        return 21.4;
                    } else {
                        return 19.2;
                    }
                } else {
                    // 項目1-3寝返りの回答をチェック
                    if (!isset($answers['1-3'])) {
                        return 0.4;
                    }

                    $turningAnswer = $answers['1-3'];

                    if ($turningAnswer === 'つかまらないでできる' || $turningAnswer === '何かにつかまればできる') {
                        // 身体機能・起居動作の中間得点を計算
                        $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                        if ($physicalScore <= 64.3) {
                            // 項目1-10洗身の回答をチェック
                            if (!isset($answers['1-10'])) {
                                return 0.4;
                            }

                            $bathingAnswer = $answers['1-10'];

                            if ($bathingAnswer === '自立（介助なし）' || $bathingAnswer === '一部介助') {
                                return 15.2;
                            } else {
                                return 17.2;
                            }
                        } else {
                            // 項目1-5座位保持の回答をチェック
                            if (!isset($answers['1-5'])) {
                                return 0.4;
                            }

                            $sittingAnswer = $answers['1-5'];

                            if ($sittingAnswer === 'できる') {
                                return 20.5;
                            } else {
                                return 17.6;
                            }
                        }
                    } else {
                        // 項目1-3寝返りが「できない」の場合
                        // 項目3-6今の季節を理解の回答をチェック
                        if (!isset($answers['3-6'])) {
                            return 0.4;
                        }

                        $seasonAnswer = $answers['3-6'];

                        if ($seasonAnswer === 'できる') {
                            return 20.8;
                        } else {
                            // 身体機能・起居動作の中間得点を計算
                            $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                            if ($physicalScore <= 15.8) {
                                return 19.3;
                            } else {
                                // 項目2-4食事摂取の回答をチェック
                                if (!isset($answers['2-4'])) {
                                    return 0.4;
                                }

                                $mealAnswer = $answers['2-4'];

                                if ($mealAnswer === '自立（介助なし）' || $mealAnswer === '見守り等') {
                                    return 19.1;
                                } else {
                                    // 項目1-1麻痺の回答をチェック
                                    if (!isset($answers['1-1'])) {
                                        return 19.0;
                                    }

                                    $paralysisAnswer = $answers['1-1'];

                                    if (empty($paralysisAnswer) || (is_array($paralysisAnswer) && empty($paralysisAnswer))) {
                                        return 19.0;
                                    } else {
                                        // 認知機能の中間得点を計算
                                        $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

                                        if ($cognitiveScore <= 36.2) {
                                            return 17.8;
                                        } else {
                                            return 16.3;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 生活機能の中間得点が63.3以上の場合の移動時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 移動時間（分）
     */
    private function calculateForHighLifeFunction(array $answers, float $lifeFunctionScore): float
    {
        if ($lifeFunctionScore <= 79.9) {
            // 項目2-6排便の回答をチェック
            if (!isset($answers['2-6'])) {
                return 0.4;
            }

            $bowelMovementAnswer = $answers['2-6'];

            if ($bowelMovementAnswer === '自立（介助なし）' || $bowelMovementAnswer === '見守り等') {
                // 項目3-4短期記憶の回答をチェック
                if (!isset($answers['3-4'])) {
                    return 0.4;
                }

                if ($answers['3-4'] === 'できる') {
                    return 4.7;
                } else {
                    return 7.8;
                }
            } else {
                // 項目2-1移乗の回答をチェック
                if (!isset($answers['2-1'])) {
                    return 0.4;
                }

                $transferAnswer = $answers['2-1'];

                if ($transferAnswer === '自立（介助なし）') {
                    return 8.2;
                } else {
                    // 項目1-1麻痺の回答をチェック
                    if (!isset($answers['1-1'])) {
                        return 14.2;
                    }

                    $paralysisAnswer = $answers['1-1'];

                    if (empty($paralysisAnswer) || (is_array($paralysisAnswer) && empty($paralysisAnswer))) {
                        return 14.2;
                    } else {
                        return 10.2;
                    }
                }
            }
        } else {
            // 生活機能の中間得点が80.0以上の場合
            // 項目2-2移動の回答をチェック
            if (!isset($answers['2-2'])) {
                return 0.4;
            }

            $movementAnswer = $answers['2-2'];

            if ($movementAnswer === '自立（介助なし）') {
                // 項目2-11ズボン等の着脱の回答をチェック
                if (!isset($answers['2-11'])) {
                    return 0.4;
                }

                $clothingAnswer = $answers['2-11'];

                if ($clothingAnswer === '自立（介助なし）' || $clothingAnswer === '見守り等') {
                    // 認知機能の中間得点を計算
                    $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

                    if ($cognitiveScore <= 61.7) {
                        return 3.8;
                    } else {
                        // 身体機能・起居動作の中間得点を計算
                        $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                        if ($physicalScore <= 87.5) {
                            return 2.0;
                        } else {
                            return 0.4;
                        }
                    }
                } else {
                    return 4.6;
                }
            } else {
                // 項目2-2移動が「見守り等」or「一部介助」or「全介助」の場合
                // 身体機能・起居動作の中間得点を計算
                $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                if ($physicalScore <= 79.4) {
                    return 7.6;
                } else {
                    return 4.1;
                }
            }
        }
    }
}
