<?php

declare(strict_types=1);

namespace Tests\Api;

use Codeception\Attribute\Before;
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
            'status' => 'not_started',
        ]);
    }

    #[Before('create')]
    public function show(ApiTester $I): void
    {
        $I->sendGetAsJson('/v1/roster/'.$this->id);
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonType([
            'id' => 'string',
            'status' => 'string',
            'created_at' => 'string',
        ]);
        $I->seeResponseContainsJson([
            'status' => 'not_started',
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
            'locations' => [],
            'shifts' => [
                [
                    'id' => 'date1 id',
                    'date' => '2021-01-01',
                    'daytime' => 'am',
                    'person_ids' => ['uta'],
                    'location_id' => 'location1',
                ],
                [
                    'id' => 'date2 id',
                    'date' => '2021-01-02',
                    'daytime' => 'pm',
                    'person_ids' => ['erwin'],
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
                            'availability' => 'no',
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
}
