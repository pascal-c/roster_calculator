<?php

declare(strict_types=1);

namespace Tests\Api;

use Codeception\Attribute\Before;
use Codeception\Attribute\Skip;
use Tests\Support\ApiTester;

final class RosterCest
{
    private string $id;

    public function createWithFailure(ApiTester $I): void
    {
        $I->sendPostAsJson('/v1/roster', ['invalid' => 'payload']);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson([
            'error' => 'shifts are missing',
        ]);
    }

    public function create(ApiTester $I): void
    {
        $response = $I->sendPostAsJson('/v1/roster', $this->getPayload());
        $this->id = $response['id'];

        $I->seeResponseCodeIs(201);
        $I->seeResponseMatchesJsonType([
            'id' => 'string',
            'status' => 'string',
            'created_at' => 'string',
        ]);
        $I->seeHttpHeader('Location', '/v1/roster/'.$this->id);
        $I->seeResponseContainsJson([
            'status' => 'completed',
        ]);
        $I->seeResponseContainsJson([
            'assignments' => [
                ['shiftId' => 'date1 id', 'personIds' => []],
                ['shiftId' => 'date2 id', 'personIds' => ['uta']],
            ],
            'personalResults' => [
                ['personId' => 'uta', 'calculatedShifts' => 2],
                ['personId' => 'erwin', 'calculatedShifts' => 1],
            ],
            'rating' => [
                'missingPerson' => 100, // nobody assigned for date1
                'maybePerson' => 1, // uta is only maybe available for date2
                'targetShifts' => 10,  // 0 for uta, 2 for erwin, 8 for unpopular-person
                'maxPerWeek' => 0,
                'locationPreferences' => 0, // no location preferences set
                'total' => 111,
            ],
        ]);
    }

    public function createWithIndividualRating(ApiTester $I): void
    {
        $response = $I->sendPostAsJson('/v1/roster', $this->getPayloadWithIndividualRating());
        $this->id = $response['id'];

        $I->seeResponseCodeIs(201);
        $I->seeResponseMatchesJsonType([
            'id' => 'string',
            'status' => 'string',
            'created_at' => 'string',
        ]);
        $I->seeHttpHeader('Location', '/v1/roster/'.$this->id);
        $I->seeResponseContainsJson([
            'status' => 'completed',
        ]);
        $I->seeResponseContainsJson([
            'assignments' => [
                ['shiftId' => 'date1 id', 'personIds' => []],
                ['shiftId' => 'date2 id', 'personIds' => ['uta']],
            ],
            'personalResults' => [
                ['personId' => 'uta', 'calculatedShifts' => 2],
                ['personId' => 'erwin', 'calculatedShifts' => 1],
                ['personId' => 'unpopular-person', 'calculatedShifts' => 0],
            ],
            'rating' => [
                'missingPerson' => 1000, // nobody assigned for date1
                'maybePerson' => 10, // uta is only maybe available for date2
                'targetShifts' => 100, // 0 for uta, 20 for erwin, 80 for unpopular-person
                'maxPerWeek' => 0,
                'total' => 1110,
            ],
        ]);
    }

    #[Before('create')]
    #[Skip('This test does not work without persistency')]
    public function _show(ApiTester $I): void
    {
        $I->sendGetAsJson('/v1/roster/'.$this->id);
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonType([
            'id' => 'string',
            'status' => 'string',
            'created_at' => 'string',
        ]);
        $I->seeResponseContainsJson([
            'status' => 'completed',
        ]);
    }

    public function showWithFailure(ApiTester $I): void
    {
        $I->sendGetAsJson('/v1/roster/invalid-id');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'error' => 'roster not found',
        ]);
    }

    private function getPayload(): array
    {
        return [
            'locations' => [
                [
                    'id' => 'location1',
                    'blockedPeopleIds' => ['erwin'],
                ],
            ],
            'shifts' => [
                [
                    'id' => 'date1 id',
                    'date' => '2021-01-01',
                    'daytime' => 'am',
                    'personIds' => ['uta'],
                    'locationId' => 'location1',
                ],
                [
                    'id' => 'date2 id',
                    'date' => '2021-01-02',
                    'daytime' => 'pm',
                    'personIds' => ['erwin'],
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
                        'targetShifts' => 2,
                        'blockedPeopleIds' => ['unpopular-person'],
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
                        'targetShifts' => 2,
                        'blockedPeopleIds' => ['unpopular-person'],
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
                [ // blocked by all clowns
                    'id' => 'unpopular-person',
                    'gender' => 'diverse',
                    'constraints' => [
                        'wishedShiftsPerMonth' => 4,
                        'maxShiftsPerMonth' => 3,
                        'maxShiftsPerDay' => 2,
                        'targetShifts' => 2,
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
