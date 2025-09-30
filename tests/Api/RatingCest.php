<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;

final class RatingCest
{
    private string $id;

    public function createWithFailure(ApiTester $I): void
    {
        $I->sendPostAsJson('/v1/rating', ['invalid' => 'payload']);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson([
            'error' => 'shifts are missing',
        ]);
    }

    public function rating(ApiTester $I): void
    {
        $I->sendPostAsJson('/v1/rating', $this->getPayload());

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson([
            'missingPerson' => 100, // one person missing for date1
            'maybePerson' => 1, // uta is only maybe available for date2
            'targetShifts' => 8, // sum of target shifts is 7, only 3 assigned (7-3)*2 = 8
            'maxPerWeek' => 0,
            'total' => 109,
        ]);
    }

    private function getPayload(): array
    {
        return [
            'locations' => [],
            'shifts' => [
                [
                    'id' => 'date1 id',
                    'date' => '2021-01-01',
                    'daytime' => 'am',
                    'personIds' => ['uta'],
                ],
                [
                    'id' => 'date2 id',
                    'date' => '2021-01-02',
                    'daytime' => 'pm',
                    'personIds' => ['erwin', 'uta'],
                ],
            ],
            'people' => [
                [
                    'id' => 'uta',
                    'gender' => 'diverse',
                    'constraints' => [
                        'wishedShiftsPerMonth' => 4,
                        'maxShiftsPerMonth' => 6,
                        'maxShiftsPerDay' => 1,
                        'targetShifts' => 3,
                    ],
                    'availabilities' => [
                        [
                            'date' => '2021-01-01',
                            'daytime' => 'am',
                            'availability' => 'yes',
                        ],
                        [
                            'date' => '2021-01-02',
                            'daytime' => 'pm',
                            'availability' => 'maybe',
                        ],
                    ],
                ],
                [
                    'id' => 'erwin',
                    'gender' => 'male',
                    'constraints' => [
                        'wishedShiftsPerMonth' => 4,
                        'maxShiftsPerMonth' => 6,
                        'maxShiftsPerDay' => 1,
                        'maxShiftsPerWeek' => 2,
                        'targetShifts' => 4,
                    ],
                    'availabilities' => [
                        [
                            'date' => '2021-01-01',
                            'daytime' => 'am',
                            'availability' => 'maybe',
                        ],
                        [
                            'date' => '2021-01-02',
                            'daytime' => 'pm',
                            'availability' => 'yes',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getPayloadWithIndividualRating(): array
    {
        $payload = $this->getPayload();
        $payload['ratingPointWeightings'] = [
            'pointsPerMissingPerson' => 1000,
            'pointsPerMaxPerWeekExceeded' => 100,
            'pointsPerMaybePerson' => 10,
            'pointsPerTargetShiftsMissed' => 20,
        ];

        return $payload;
    }
}
