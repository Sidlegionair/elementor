<?php
namespace Elementor\Tests\Phpunit\Elementor\Modules\Usage;

use Elementor\Modules\Usage\Module;
use Elementor\Plugin;
use Elementor\Testing\Elementor_Test_Base;
use Elementor\Testing\Factories\Documents;
use Elementor\Tests\Phpunit\Elementor\Modules\Usage\DynamicTags\Link;
use Elementor\Tests\Phpunit\Elementor\Modules\Usage\DynamicTags\Title;

class Test_Module extends Elementor_Test_Base {
	/**
	 * TODO: Remove - Backwards compatibility.
	 *
	 * @var array
	 */
	static $document_mock_default = [
		'settings' => [
			'post_status' => 'publish',
		],
		'elements' => [
			[
				'id' => 'd50d8c5',
				'elType' => 'section',
				'isInner' => false,
				'settings' => [],
				'elements' => [
					[
						'id' => 'a2e9b68',
						'elType' => 'column',
						'isInner' => false,
						'settings' => [ '_column_size' => 100, ],
						'elements' => [
							[
								'id' => '5a1e8e5',
								'elType' => 'widget',
								'isInner' => false,
								'settings' => [ 'text' => 'I\'m not a default', ],
								'elements' => [],
								'widgetType' => 'button',
							],
						],
					],
				],
			],
		],
	];

	/**
	 * @var Module
	 */
	private $module;

	/**
	 * @var bool
	 */
	private $isDynamicTags = false;

	public function setUp() {
		parent::setUp();

		wp_set_current_user( $this->factory()->create_and_get_administrator_user()->ID );

		$this->module = $module = Module::instance();
	}

	private function get_global_usage_by_document( $document ) {
		$global_usage = get_option( Module::OPTION_NAME, [] );

		$document_name = $document->get_name();
		if ( ! empty( $global_usage[ $document_name] ) ) {
			$global_usage = $global_usage[ $document_name ];
		}

		return $global_usage;
	}

	private function generate_document_with_duplicated_widget() {
		$document = $this->factory()->documents->create_and_get();
		$elementor_data = $document->get_json_meta( '_elementor_data' );

		$section = &$elementor_data[ 0 ];
		$column = &$section['elements'][ 0 ];
		$widget = &$column['elements'][ 0 ];

		// Duplicate widget.
		$column['elements'][] = $widget;

		// Find better way.
		$document->save( [
			'settings' => [
				'post_status' => 'publish'
			],
			'elements' => $elementor_data,
		] );

		return $document;
	}

	private function ensure_dynamic_tags() {
		if ( ! $this->isDynamicTags ) {
			Plugin::$instance->dynamic_tags->register_tag( new Title() );
			Plugin::$instance->dynamic_tags->register_tag( new Link() );

			$this->isDynamicTags = true;
		}
	}

	// Old name 'test_doc_type_count'.
	public function test_get_doc_type_count() {
		// Arrange.
		$doc_type = self::factory()->documents->publish_and_get()->get_name();
		$doc_class = Plugin::$instance->documents->get_document_type( $doc_type );

		// Act.
		$doc_count = $this->module->get_doc_type_count( $doc_class, $doc_type );

		// Assert.
		$this->assertEquals( 1, $doc_count );
	}

	// Old name 'test_formatted_usage'.
	public function test_get_formatted_usage() {
		// Arrange.
		$document = self::factory()->documents->publish_and_get();

		// Act.
		$formatted_usage = $this->module->get_formatted_usage();

		// Check if button exist and it value is `1`.
		$this->assertEquals( 1, $formatted_usage[ $document->get_name() ]['elements']['Button'] );
	}

	// Old name 'test_recalc'.
	public function test_recalc_usage() {
		// Arrange.
		$document = $this->factory()->documents->publish_and_get();
		$this->factory()->documents->publish_and_get();

		// Clear global usage.
		update_option( Module::OPTION_NAME, [] );

		// Act.
		$this->module->recalc_usage();


		// Assert.
		$this->assertEquals( 2, $this->get_global_usage_by_document( $document )['button']['count'] );
	}

	// Old part of ' test_add_to_global'
	public function test_add_to_global() {
		// Act.
		$document = $this->factory()->documents->publish_and_get();

		// Assert.
		$this->assertTrue( !! $this->get_global_usage_by_document( $document ) );
	}

	// Old part of 'test_add_to_global'.
	public function test_add_to_global__ensure_elements() {
		// Arrange.
		$count = 2;
		for ( $i = 0 ; $i != $count; $i++ ) {
			// Act.
			$document = $this->factory()->documents->publish_and_get();

			// Assert.
			$global_document_usage = $this->get_global_usage_by_document( $document );
			$this->assertEquals( $i + 1, $global_document_usage['button']['count'] );
		}
	}

	// Part of old 'test_elements'
	public function test_add_to_global__ensure_elements__from_same_document() {
		// Act.
		$document = $this->generate_document_with_duplicated_widget();

		// Assert.
		$global_document_usage = $this->get_global_usage_by_document( $document );
		$this->assertEquals( 2, $global_document_usage['button']['count'] );
	}

	// Old part of 'test_controls'.
	public function test_add_to_global__ensure_controls() {
		// Arrange.
		$count = 2;
		for ( $i = 0 ; $i != $count; $i++ ) {
			// Act.
			$document = $this->factory()->documents->publish_and_get();

			// Assert.
			$global_document_usage = $this->get_global_usage_by_document( $document );
			$this->assertEquals( $i + 1, $global_document_usage['button']['controls']['content']['section_button']['text'] );
		}
	}

	// Old name: 'test_remove_data_after_delete_post'.
	public function test_remove_from_global() {
		// Arrange.
		$document = $this->factory()->documents->publish_and_get();

		// Assert.
		$this->assertTrue( !! $this->get_global_usage_by_document( $document ) );

		// Act.
		wp_delete_post( $document->get_id(), true );

		// Assert.
		$this->assertFalse( !! $this->get_global_usage_by_document( $document ) );
	}

	// New test.
	public function test_remove_from_global__ensure_elements() {
		// Arrange.
		$count = 2;
		$documents = [];

		for ( $i = 0 ; $i < $count; $i++ ) {
			$documents [] = $this->factory()->documents->publish_and_get();
		}

		$i = count( $documents );
		foreach ( $documents as $document ) {
			// Assert.
			$global_document_usage = $this->get_global_usage_by_document( $document );
			$this->assertEquals( $i, $global_document_usage['button']['count'] );

			// Act.
			wp_delete_post( $document->get_id(), true );


			$i--;
		}
	}

	// Part of old 'test_elements'
	public function test_remove_from_global__ensure_elements_from_same_document() {
		// Arrange.
		$document = $this->generate_document_with_duplicated_widget();
		$elementor_data = $document->get_json_meta( '_elementor_data' );

		$section = &$elementor_data[ 0 ];
		$column = &$section['elements'][ 0 ];

		unset( $column['elements'][ 1 ] );

		// Act.
		$document->save( [
			'settings' => [
				'post_status' => 'publish'
			],
			'elements' => $elementor_data,
		] );

		// Assert.
		$global_document_usage = $this->get_global_usage_by_document( $document );
		$this->assertEquals( 1, $global_document_usage['button']['count'] );
	}

	// Old part of 'test_controls'.
	public function test_remove_from_global__ensure_controls() {
		// Arrange.
		$count = 2;
		$documents = [];

		for ( $i = 0 ; $i < $count; $i++) {
			$documents [] = $this->factory()->documents->publish_and_get();
		}

		foreach ( $documents as $document ) {
			// Assert.
			$global_document_usage = $this->get_global_usage_by_document( $document );
			$this->assertEquals( $count, $global_document_usage['button']['controls']['content']['section_button']['text'] );

			// Act.
			wp_delete_post( $document->get_id(), true );

			$count--;
		}
	}

	// Old name 'test_dynamic_control'.
	public function test_remove_from_global__ensure_dynamic_controls() {
		// Arrange.
		$this->ensure_dynamic_tags();

		$count = 2;
		$documents = [];

		for ( $i = 0 ; $i < $count; $i++) {
			$document = $this->factory()->documents->publish_and_get( [
				'meta_input' => [
					'_elementor_data' => Documents::DOCUMENT_DATA_MOCK_WITH_DYNAMIC_WIDGET,
				]
			] );

			$documents [] = $document;
		}

		foreach ( $documents as $document ) {
			$global_document_usage = $this->get_global_usage_by_document( $document );

			$link_controls_count = $global_document_usage['heading']['controls']['content']['section_title']['link'];
			$title_controls_count = $global_document_usage['heading']['controls']['content']['section_title']['link'];

			// Assert.
			$this->assertEquals( $count, $link_controls_count );
			$this->assertEquals( $count, $title_controls_count );
			$this->assertEquals( $link_controls_count + $title_controls_count,
				$global_document_usage['heading']['controls']['general']['__dynamic__']['count']
			);

			// Act.
			wp_delete_post( $document->get_id(), true );

			$count--;
		}
	}

	// Old name 'test_remove_from_global'.
	public function test_remove_from_global__ensure_elements_removed_by_empty_document() {
		// Arrange.
		$document = $this->factory()->documents->publish_and_get();

		// Act.
		$document->save( Documents::DOCUMENT_DATA_MOCK_WITHOUT_ELEMENTS );

		// Assert.
		$this->assertFalse( !! $this->get_global_usage_by_document( $document ) );
	}

	// Old name: 'test_draft_and_republish'.
	public function test_remove_from_global__ensure__draft_removed_and_can_be_republished() {
		// Arrange.
		$document = self::factory()->documents->publish_and_get();

		// Act - Put to draft.
		self::factory()->documents->update_object( $document->get_id(), [
			'post_status' => 'draft'
		] );

		// Assert.
		$this->assertFalse( !! $this->get_global_usage_by_document( $document ) );

		// Act - Put to published.
		self::factory()->documents->update_object( $document->get_id(), [
			'post_status' => 'publish'
		] );

		// Assert.
		$this->assertTrue( !! $this->get_global_usage_by_document( $document ) );
	}

	// Old name: 'test_draft_and_private'.
	public function test_remove_from_global__ensure_draft_removed_and_private_can_be_republished() {
		// Arrange.
		$document = self::factory()->documents->publish_and_get();

		// Act - Put to draft.
		self::factory()->documents->update_object( $document->get_id(), [
			'post_status' => 'draft'
		] );

		// Assert.
		$this->assertFalse( !! $this->get_global_usage_by_document( $document ) );

		// Act - Put to published.
		self::factory()->documents->update_object( $document->get_id(), [
			'post_status' => 'private'
		] );

		// Assert.
		$this->assertTrue( !! $this->get_global_usage_by_document( $document ) );
	}

	/**
	 * Old name: 'test_autosave_and_publish'.
	 * Cover issue: 'Widgets count shows negative values in some cases'.
	 */
	public function test_remove_from_global__ensure_autosave_not_affecting() {
		// Arrange - Create additional document in order that after remove from global the usage will not be empty.
		$this->factory()->documents->publish_and_get(); // Adds one button.
		$document = $this->factory()->documents->publish_and_get(); // Adds another one button

		// Act - Create document using autosave.
		$document->get_autosave( 0, true )->save( Documents::DEFAULT_DOCUMENT_DATA_MOCK );

		// Assert - Still only two buttons.
		$this->assertEquals( 2, $this->get_global_usage_by_document( $document )['button']['count'] );
	}

	public function test_save_document_usage() {
		// Arrange.
		$document = $this->factory()->documents->publish_and_get();

		// Act.
		$usage = $document->get_meta( Module::META_KEY );

		// Assert.
		$this->assertEquals( 1, $usage['button']['count'] );
	}
}
