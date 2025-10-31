<?php

namespace App\Services\CareTimeCalculators;

use App\Services\BaseCareTimeCalculator;

/**
 * 機能訓練関連行為に関する要介護認定基準時間を算出するクラス
 * 時間の表示範囲：0.5～15.4分
 */
class FunctionalTrainingCareTimeCalculator extends BaseCareTimeCalculator
{
    /**
     * 機能訓練関連行為に関する要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return float 機能訓練関連行為に関する時間（分）
     */
    public function calculate(array $answers): float
    {
        // 項目3-7場所の理解の回答をチェック
        if (!isset($answers['3-7'])) {
            return 0.5;
        }

        $placeAnswer = $answers['3-7'];

        if ($placeAnswer === 'できる') {
            return $this->calculateForPlaceUnderstanding($answers);
        } else {
            return $this->calculateForNoPlaceUnderstanding($answers);
        }
    }

    /**
     * 場所の理解ができる場合の機能訓練関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 機能訓練関連行為時間（分）
     */
    private function calculateForPlaceUnderstanding(array $answers): float
    {
        // 項目1-2拘縮の回答をチェック
        if (!isset($answers['1-2'])) {
            return 0.5;
        }

        $contractureAnswer = $answers['1-2'];
        $hasShoulderContracture = is_array($contractureAnswer) && in_array('肩関節', $contractureAnswer);

        if (!$hasShoulderContracture) {
            // 項目1-2拘縮に「肩関節」がない場合
            // 身体機能・起居動作の中間得点を計算
            $physicalScore = $this->calculatePhysicalFunctionScore($answers);

            if ($physicalScore <= 80.4) {
                // 項目1-1麻痺の回答をチェック
                if (!isset($answers['1-1'])) {
                    return 0.5;
                }

                $paralysisAnswer = $answers['1-1'];
                $paralysisIsNoneOrSingle = empty($paralysisAnswer) || 
                    (is_array($paralysisAnswer) && empty($paralysisAnswer)) ||
                    (is_string($paralysisAnswer) && $paralysisAnswer === 'いずれか一肢のみ');

                if ($paralysisIsNoneOrSingle) {
                    // 精神・行動障害の中間得点を計算
                    $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                    if ($mentalScore <= 99.5) {
                        // 生活機能の中間得点を計算
                        $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

                        if ($lifeFunctionScore <= 64.2) {
                            return 8.9;
                        } else {
                            return 6.1;
                        }
                    } else {
                        return 10.5;
                    }
                } else {
                    // 項目1-1麻痺が「両下肢のみ」or「左上下肢あるいは右上下肢のみ」or「その他の四肢の麻痺」の場合
                    // 社会生活への適応の中間得点を計算
                    $socialScore = $this->calculateSocialAdaptationScore($answers);

                    if ($socialScore <= 21.3) {
                        return 7.7;
                    } else {
                        // 生活機能の中間得点を計算
                        $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

                        if ($lifeFunctionScore <= 72.9) {
                            // 精神・行動障害の中間得点を計算
                            $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                            if ($mentalScore <= 97.3) {
                                return 2.0;
                            } else {
                                return 4.0;
                            }
                        } else {
                            return 7.1;
                        }
                    }
                }
            } else {
                // 身体機能・起居動作の中間得点が80.5以上の場合
                // 精神・行動障害の中間得点を計算
                $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                if ($mentalScore <= 90.8) {
                    return 2.2;
                } else {
                    // 項目1-11つめ切りの回答をチェック
                    if (!isset($answers['1-11'])) {
                        return 0.5;
                    }

                    $nailAnswer = $answers['1-11'];

                    if ($nailAnswer === '自立（介助なし）' || $nailAnswer === '一部介助') {
                        return 6.1;
                    } else {
                        return 4.5;
                    }
                }
            }
        } else {
            // 項目1-2拘縮に「肩関節」がある場合
            // 生活機能の中間得点を計算
            $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

            if ($lifeFunctionScore <= 35.6) {
                return 15.4;
            } else {
                // 項目5-3日常の意思決定の回答をチェック
                if (!isset($answers['5-3'])) {
                    return 0.5;
                }

                $decisionAnswer = $answers['5-3'];

                if ($decisionAnswer === 'できる') {
                    // 項目2-5排尿の回答をチェック
                    if (!isset($answers['2-5'])) {
                        return 0.5;
                    }

                    $urinationAnswer = $answers['2-5'];

                    if ($urinationAnswer === '自立（介助なし）' || $urinationAnswer === '見守り等') {
                        return 7.6;
                    } else {
                        return 6.0;
                    }
                } else {
                    // 項目5-3が「特別な場合を除いてできる」or「日常的に困難」or「できない」の場合
                    return 10.4;
                }
            }
        }
    }

    /**
     * 場所の理解ができない場合の機能訓練関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 機能訓練関連行為時間（分）
     */
    private function calculateForNoPlaceUnderstanding(array $answers): float
    {
        // 項目1-1麻痺の回答をチェック
        if (!isset($answers['1-1'])) {
            return 0.5;
        }

        $paralysisAnswer = $answers['1-1'];
        $paralysisIsNoneOrSingleOrBothLegs = empty($paralysisAnswer) || 
            (is_array($paralysisAnswer) && empty($paralysisAnswer)) ||
            (is_string($paralysisAnswer) && ($paralysisAnswer === 'いずれか一肢のみ' || $paralysisAnswer === '両下肢のみ')) ||
            (is_array($paralysisAnswer) && (in_array('いずれか一肢のみ', $paralysisAnswer) || in_array('両下肢のみ', $paralysisAnswer)));

        if ($paralysisIsNoneOrSingleOrBothLegs) {
            // 項目2-3えん下の回答をチェック
            if (!isset($answers['2-3'])) {
                return 0.5;
            }

            $swallowingAnswer = $answers['2-3'];

            if ($swallowingAnswer === 'できる') {
                return $this->calculateForSwallowing($answers);
            } elseif ($swallowingAnswer === '見守り等') {
                return $this->calculateForSwallowingWatch($answers);
            } else {
                // 項目2-3えん下が「できない」の場合
                return 7.0;
            }
        } else {
            // 項目1-1麻痺が「左上下肢あるいは右上下肢のみ」or「その他の四肢の麻痺」の場合
            return $this->calculateForSevereParalysis($answers, $paralysisAnswer);
        }
    }

    /**
     * えん下ができる場合の機能訓練関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 機能訓練関連行為時間（分）
     */
    private function calculateForSwallowing(array $answers): float
    {
        // 認知機能の中間得点を計算
        $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

        if ($cognitiveScore <= 37.6) {
            // 精神・行動障害の中間得点を計算
            $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

            if ($mentalScore <= 88.4) {
                return 2.0;
            } else {
                return 4.6;
            }
        } else {
            // 項目5-1薬の内服の回答をチェック
            if (!isset($answers['5-1'])) {
                return 0.5;
            }

            $medicationAnswer = $answers['5-1'];

            if ($medicationAnswer === '自立（介助なし）' || $medicationAnswer === '一部介助') {
                // 認知機能の中間得点を計算
                $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

                if ($cognitiveScore <= 54.1) {
                    return 1.1;
                } else {
                    return 4.1;
                }
            } else {
                // 項目5-1薬の内服が「全介助」の場合
                // 項目5-4集団への不適応の回答をチェック
                if (!isset($answers['5-4'])) {
                    return 0.5;
                }

                $groupAnswer = $answers['5-4'];

                if ($groupAnswer === 'ない') {
                    // 項目1-1麻痺の回答をチェック
                    if (!isset($answers['1-1'])) {
                        return 0.5;
                    }

                    $paralysisAnswer = $answers['1-1'];
                    $paralysisIsNoneOrSingle = empty($paralysisAnswer) || 
                        (is_array($paralysisAnswer) && empty($paralysisAnswer)) ||
                        (is_string($paralysisAnswer) && $paralysisAnswer === 'いずれか一肢のみ');

                    if ($paralysisIsNoneOrSingle) {
                        // 項目2-1移乗の回答をチェック
                        if (!isset($answers['2-1'])) {
                            return 0.5;
                        }

                        $transferAnswer = $answers['2-1'];

                        if ($transferAnswer === '自立（介助なし）' || $transferAnswer === '見守り等') {
                            return 5.1;
                        } else {
                            return 10.5;
                        }
                    } else {
                        // 項目1-1麻痺が「両下肢のみ」の場合
                        return 4.6;
                    }
                } else {
                    // 項目5-4集団への不適応が「ときどきある」or「ある」の場合
                    return 3.9;
                }
            }
        }
    }

    /**
     * えん下が見守り等の場合の機能訓練関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 機能訓練関連行為時間（分）
     */
    private function calculateForSwallowingWatch(array $answers): float
    {
        // 項目5-3日常の意思決定の回答をチェック
        if (!isset($answers['5-3'])) {
            return 0.5;
        }

        $decisionAnswer = $answers['5-3'];

        if ($decisionAnswer === 'できる' || 
            $decisionAnswer === '特別な場合を除いてできる' || 
            $decisionAnswer === '日常的に困難') {
            // 項目3-1意思の伝達の回答をチェック
            if (!isset($answers['3-1'])) {
                return 0.5;
            }

            $communicationAnswer = $answers['3-1'];

            if ($communicationAnswer === '調査対象者が意思を他者に伝達できる' || 
                $communicationAnswer === 'ときどき伝達できる' || 
                $communicationAnswer === 'ほとんど伝達できない') {
                if ($decisionAnswer === 'できる' || $decisionAnswer === '特別な場合を除いてできる') {
                    return 1.6;
                } else {
                    return 3.9;
                }
            } else {
                // 項目3-1意思の伝達が「できない」の場合
                return 0.5;
            }
        } else {
            // 項目5-3日常の意思決定が「できない」の場合
            // 項目1-1麻痺の回答をチェック
            if (!isset($answers['1-1'])) {
                return 5.7;
            }

            $paralysisAnswer = $answers['1-1'];
            $paralysisIsNoneOrSingleOrBoth = empty($paralysisAnswer) || 
                (is_array($paralysisAnswer) && empty($paralysisAnswer)) ||
                (is_string($paralysisAnswer) && ($paralysisAnswer === 'いずれか一肢のみ' || 
                 $paralysisAnswer === '両下肢のみ' || 
                 $paralysisAnswer === '左上下肢あるいは右上下肢のみ'));

            if ($paralysisIsNoneOrSingleOrBoth) {
                // 精神・行動障害の中間得点を計算
                $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                if ($mentalScore <= 91.2) {
                    return 2.5;
                } else {
                    return 4.6;
                }
            } else {
                // 項目1-1麻痺がない場合
                return 5.7;
            }
        }
    }

    /**
     * 重度の麻痺がある場合の機能訓練関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @param mixed $paralysisAnswer 麻痺の回答
     * @return float 機能訓練関連行為時間（分）
     */
    private function calculateForSevereParalysis(array $answers, $paralysisAnswer): float
    {
        $isLeftRightUpperLower = (is_string($paralysisAnswer) && $paralysisAnswer === '左上下肢あるいは右上下肢のみ') ||
            (is_array($paralysisAnswer) && in_array('左上下肢あるいは右上下肢のみ', $paralysisAnswer));

        if ($isLeftRightUpperLower) {
            // 項目1-1麻痺が「左上下肢あるいは右上下肢のみ」の場合
            // 項目5-3日常の意思決定の回答をチェック
            if (!isset($answers['5-3'])) {
                return 0.5;
            }

            $decisionAnswer = $answers['5-3'];

            if ($decisionAnswer === 'できる' || 
                $decisionAnswer === '特別な場合を除いてできる' || 
                $decisionAnswer === '日常的に困難') {
                return 4.6;
            } else {
                return 11.6;
            }
        } else {
            // 項目1-1麻痺が「その他の四肢の麻痺」の場合
            // 項目5-4集団への不適応の回答をチェック
            if (!isset($answers['5-4'])) {
                return 0.5;
            }

            $groupAnswer = $answers['5-4'];

            if ($groupAnswer === 'ない') {
                // 身体機能・起居動作の中間得点を計算
                $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                if ($physicalScore <= 28.5) {
                    // 項目1-12視力の回答をチェック
                    if (!isset($answers['1-12'])) {
                        return 0.5;
                    }

                    $visionAnswer = $answers['1-12'];

                    if ($visionAnswer === '普通（日常生活に支障がない）') {
                        // 精神・行動障害の中間得点を計算
                        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                        if ($mentalScore <= 96.7) {
                            return 1.9;
                        } else {
                            return 3.3;
                        }
                    } else {
                        // 生活機能の中間得点を計算
                        $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

                        if ($lifeFunctionScore <= 5.2) {
                            // 身体機能・起居動作の中間得点を計算
                            $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                            if ($physicalScore <= 1.6) {
                                return 2.5;
                            } else {
                                return 3.2;
                            }
                        } else {
                            return 6.5;
                        }
                    }
                } else {
                    return 7.8;
                }
            } else {
                // 項目5-4集団への不適応が「ときどきある」or「ある」の場合
                return 7.8;
            }
        }
    }
}
