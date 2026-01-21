<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;

final class RatingCest
{
    public function _before(ApiTester $I): void
    {
        $I->amBearerAuthenticated('123');
    }

    public function ratingWithoutToken(ApiTester $I): void
    {
        $I->unsetHttpHeader('Authorization');
        $I->sendPostAsJson('/v1/rating', $this->getPayload());
        $I->seeResponseCodeIs(401);
        $I->seeResponseEquals('"Unauthorized - Bearer Authentication required"');
    }

    public function ratingWithInvalidToken(ApiTester $I): void
    {
        $I->amBearerAuthenticated('invalid-token');
        $I->sendPostAsJson('/v1/rating', $this->getPayload());
        $I->seeResponseCodeIs(401);
        $I->seeResponseEquals('"Unauthorized - invalid token"');
    }

    public function ratingWithInvalidPayload(ApiTester $I): void
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
            'targetShifts' => 20, // sum of target shifts is 7, only 3 assigned 2 points for uta + 3*3*2 for erwin = 20
            'maxPerWeek' => 0,
            'locationPreferences' => 6, // uta has 6 points for location1, erwin has location preference default points 1
            'personNotInTeam' => 3, // uta is not in team for date2
            'total' => 130,
        ]);
    }

    private function getPayload(): array
    {
        return [
            'locations' => [
                [
                    'id' => 'location1',
                ],
            ],
            'shifts' => [
                [
                    'id' => 'date1 id',
                    'date' => '2021-01-01',
                    'daytime' => 'am',
                    'assignedPeople' => ['uta'],
                    'locationId' => 'location1',
                ],
                [
                    'id' => 'date2 id',
                    'date' => '2021-01-02',
                    'daytime' => 'pm',
                    'assignedPeople' => ['erwin', 'uta'],
                    'team' => ['erwin'],
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
                        'locationPreferences' => [
                            [
                                'locationId' => 'location1',
                                'points' => 5,
                            ],
                        ],
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
                        'locationPreferenceDefaultPoints' => 1,
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
}
