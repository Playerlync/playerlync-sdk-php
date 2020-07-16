<?php


namespace PlMigration\test\UnitTest;

use PlMigration\Service\Plapi\ParentChildGetService;

class ParentChildGetServiceTest extends UnitTest
{
    /**
     * @test
     */
    public function mergeRequestOptions()
    {
        $service = new ParentChildGetService('/plform/{bundle_id}/submissions','/bundles', ['query' => [
            'filter' => 'bundle_type|eq|plform',
        ]], ['query' => [
            'include_children' => '1',
        ]]);

        $result = $service->mergeOptions(['query' => [
            'filter'=> 'test'
            ]
        ], ['query' => [
            'include_children' => '1',
        ]]);

        $this->assertArrayHasKey('include_children', $result['query']);
        $this->assertArrayHasKey('filter', $result['query']);
    }
}