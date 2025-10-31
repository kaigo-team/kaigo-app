<?php

namespace App\Services\CareTimeCalculators;

use App\Services\BaseCareTimeCalculator;

/**
 * 清潔保持に関する要介護認定基準時間を算出するクラス
 * 時間の表示範囲：1.2～24.3分
 */
class HygieneCareTimeCalculator extends BaseCareTimeCalculator
{
    /**
     * 清潔保持に関する要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return float 清潔保持に関する時間（分）
     */
    public function calculate(array $answers): float
    {
        // 生活機能の中間得点を計算
        $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

        if ($lifeFunctionScore <= 14.4) {
            return $this->calculateForLowLifeFunction($answers, $lifeFunctionScore);
        } else {
            return $this->calculateForHighLifeFunction($answers);
        }
    }

    /**
     * 生活機能の中間得点が14.4以下の場合の清潔保持時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $lifeFunctionScore 生活機能の中間得点
     * @return float 清潔保持時間（分）
     */
    private function calculateForLowLifeFunction(array $answers, float $lifeFunctionScore): float
    {
        // 項目3-5自分の名前を言うことの回答をチェック
        if (!isset($answers['3-5'])) {
            return 1.2;
        }

        $nameAnswer = $answers['3-5'];

        if ($nameAnswer === 'できる') {
            // 認知機能の中間得点を計算
            $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

            if ($cognitiveScore <= 58.1) {
                if ($lifeFunctionScore <= 9.4) {
                    return 2.2;
                } else {
                    return 4.2;
                }
            } else {
                return 5.4;
            }
        } else {
            // 項目3-5が「できない」の場合
            // 身体機能・起居動作の中間得点を計算
            $physicalScore = $this->calculatePhysicalFunctionScore($answers);

            if ($physicalScore <= 10.1) {
                return 0.4;
            } else {
                // 精神・行動障害の中間得点を計算
                $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                if ($mentalScore <= 80.8) {
                    return 3.6;
                } else {
                    // 項目1-12視力の回答をチェック
                    if (!isset($answers['1-12'])) {
                        return 1.3;
                    }

                    $visionAnswer = $answers['1-12'];

                    if ($visionAnswer === '普通（日常生活に支障がない）' || $visionAnswer === '約1m離れた視力確認表の図が見える') {
                        // 精神・行動障害の中間得点を計算
                        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                        if ($mentalScore <= 98.8) {
                            return 1.7;
                        } else {
                            return 2.8;
                        }
                    } else {
                        return 1.3;
                    }
                }
            }
        }
    }

    /**
     * 生活機能の中間得点が14.5以上の場合の清潔保持時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 清潔保持時間（分）
     */
    private function calculateForHighLifeFunction(array $answers): float
    {
        // 精神・行動障害の中間得点を計算
        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

        if ($mentalScore <= 95.8) {
            // 項目2-12外出頻度の回答をチェック
            if (!isset($answers['2-12'])) {
                return 1.2;
            }

            $outingAnswer = $answers['2-12'];

            if ($outingAnswer === '週1回以上' || $outingAnswer === '月1回以上') {
                // 項目3-1意思の伝達の回答をチェック
                if (!isset($answers['3-1'])) {
                    return 1.2;
                }

                $communicationAnswer = $answers['3-1'];

                if ($communicationAnswer === '調査対象者が意思を他者に伝達できる') {
                    return 10.9;
                } else {
                    return 8.0;
                }
            } else {
                // 項目2-12外出頻度が「月1回未満」の場合
                return $this->calculateForLowOutingFrequency($answers);
            }
        } else {
            // 精神・行動障害の中間得点が95.9以上の場合
            return $this->calculateForHighMentalScore($answers);
        }
    }

    /**
     * 外出頻度が低い場合の清潔保持時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 清潔保持時間（分）
     */
    private function calculateForLowOutingFrequency(array $answers): float
    {
        // 項目1-3寝返りの回答をチェック
        if (!isset($answers['1-3'])) {
            return 1.2;
        }

        $turningAnswer = $answers['1-3'];

        if ($turningAnswer === 'つかまらないでできる') {
            // 精神・行動障害の中間得点を計算
            $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

            if ($mentalScore <= 69.0) {
                return 6.5;
            } else {
                // 項目2-1移乗の回答をチェック
                if (!isset($answers['2-1'])) {
                    return 1.2;
                }

                $transferAnswer = $answers['2-1'];

                if ($transferAnswer === '自立（介助なし）') {
                    // 項目3-1意思の伝達の回答をチェック
                    if (!isset($answers['3-1'])) {
                        return 1.2;
                    }

                    $communicationAnswer = $answers['3-1'];

                    if ($communicationAnswer === '調査対象者が意思を他者に伝達できる') {
                        return 4.7;
                    } else {
                        return 3.0;
                    }
                } else {
                    return 6.3;
                }
            }
        } else {
            // 項目1-3寝返りが「何かにつかまればできる」or「できない」の場合
            // 項目3-1意思の伝達の回答をチェック
            if (!isset($answers['3-1'])) {
                return 1.2;
            }

            $communicationAnswer = $answers['3-1'];

            if ($communicationAnswer === '調査対象者が意思を他者に伝達できる') {
                // 生活機能の中間得点を計算
                $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

                if ($lifeFunctionScore <= 58.9) {
                    if ($lifeFunctionScore <= 40.0) {
                        return 7.1;
                    } else {
                        return 11.3;
                    }
                } else {
                    // 項目1-9片足での立位保持の回答をチェック
                    if (!isset($answers['1-9'])) {
                        return 1.2;
                    }

                    $singleLegStandingAnswer = $answers['1-9'];

                    if ($singleLegStandingAnswer === '支えなしでできる' || $singleLegStandingAnswer === '何か支えがあればできる') {
                        return 7.7;
                    } else {
                        return 5.8;
                    }
                }
            } else {
                // 項目3-1意思の伝達が「ときどき伝達できる」or「ほとんど伝達できない」or「できない」の場合
                // 生活機能の中間得点を計算
                $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

                if ($lifeFunctionScore <= 69.7) {
                    // 項目2-4食事摂取の回答をチェック
                    if (!isset($answers['2-4'])) {
                        return 1.2;
                    }

                    $mealAnswer = $answers['2-4'];

                    if ($mealAnswer === '自立（介助なし）') {
                        return 6.7;
                    } else {
                        // 精神・行動障害の中間得点を計算
                        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                        if ($mentalScore <= 74.4) {
                            return 6.4;
                        } else {
                            // 項目1-12視力の回答をチェック
                            if (!isset($answers['1-12'])) {
                                return 5.7;
                            }

                            $visionAnswer = $answers['1-12'];

                            if ($visionAnswer === '普通（日常生活に支障がない）') {
                                // 精神・行動障害の中間得点を計算
                                $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                                if ($mentalScore <= 88.2) {
                                    return 4.9;
                                } else {
                                    return 3.6;
                                }
                            } else {
                                return 5.7;
                            }
                        }
                    }
                } else {
                    return 8.2;
                }
            }
        }
    }

    /**
     * 精神・行動障害の中間得点が高い場合の清潔保持時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 清潔保持時間（分）
     */
    private function calculateForHighMentalScore(array $answers): float
    {
        // 項目2-1移乗の回答をチェック
        if (!isset($answers['2-1'])) {
            return 1.2;
        }

        $transferAnswer = $answers['2-1'];

        if ($transferAnswer === '自立（介助なし）') {
            // 項目5-2金銭の管理の回答をチェック
            if (!isset($answers['5-2'])) {
                return 1.2;
            }

            $moneyAnswer = $answers['5-2'];

            if ($moneyAnswer === '自立（介助なし）' || $moneyAnswer === '一部介助') {
                // 項目2-11ズボン等の着脱の回答をチェック
                if (!isset($answers['2-11'])) {
                    return 1.2;
                }

                $clothingAnswer = $answers['2-11'];

                if ($clothingAnswer === '自立（介助なし）') {
                    // 社会生活への適応の中間得点を計算
                    $socialScore = $this->calculateSocialAdaptationScore($answers);

                    if ($socialScore <= 65.5) {
                        return 3.2;
                    } else {
                        return 4.7;
                    }
                } else {
                    return 5.1;
                }
            } else {
                // 項目5-2金銭の管理が「全介助」の場合
                return 2.7;
            }
        } else {
            // 項目2-1移乗が「見守り等」or「一部介助」or「全介助」の場合
            // 認知機能の中間得点を計算
            $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

            if ($cognitiveScore <= 51.0) {
                return 3.6;
            } else {
                // 項目2-7口腔清潔の回答をチェック
                if (!isset($answers['2-7'])) {
                    return 1.2;
                }

                $oralCareAnswer = $answers['2-7'];

                if ($oralCareAnswer === '自立（介助なし）' || $oralCareAnswer === '一部介助') {
                    // 身体機能・起居動作の中間得点を計算
                    $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                    if ($physicalScore <= 40.1) {
                        return 9.4;
                    } else {
                        // 項目2-3えん下の回答をチェック
                        if (!isset($answers['2-3'])) {
                            return 1.2;
                        }

                        $swallowingAnswer = $answers['2-3'];

                        if ($swallowingAnswer === 'できる') {
                            // 項目2-1移乗の回答をチェック
                            if (!isset($answers['2-1'])) {
                                return 1.2;
                            }

                            $transferAnswer = $answers['2-1'];

                            if ($transferAnswer === '見守り等') {
                                return 4.5;
                            } else {
                                // 項目2-2移動の回答がどの場合でも同じロジック（ルールファイルに値が記載されていない）
                                return 4.5; // 暫定値（実際のロジックに合わせて調整が必要）
                            }
                        } else {
                            return 7.8;
                        }
                    }
                } else {
                    // 項目2-7口腔清潔が「全介助」の場合
                    return 4.6;
                }
            }
        }
    }
}
