<?php

namespace srag\Plugins\SrTile\Tile;

use ilCheckboxInputGUI;
use ilColorPickerInputGUI;
use ilException;
use ilFormSectionHeaderGUI;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Location;
use ilImageFileInputGUI;
use ilNotifications4PluginsPlugin;
use ilNumberInputGUI;
use ilRadioGroupInputGUI;
use ilRadioOption;
use ilSelectInputGUI;
use ilSrTilePlugin;
use srag\CustomInputGUIs\SrTile\PropertyFormGUI\PropertyFormGUI;
use srag\Plugins\SrTile\Utils\SrTileTrait;
use SrTileGUI;
use Throwable;

/**
 * Class TileFormGUI
 *
 * @package srag\Plugins\srTile\Tile
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class TileFormGUI extends PropertyFormGUI {

	use SrTileTrait;
	const PLUGIN_CLASS_NAME = ilSrTilePlugin::class;
	const LANG_MODULE = SrTileGUI::LANG_MODULE_TILE;
	/**
	 * @var Tile
	 */
	protected $tile;


	/**
	 * TileFormGUI constructor
	 *
	 * @param SrTileGUI $parent
	 * @param Tile      $tile
	 *
	 * @throws ilException
	 */
	public function __construct(SrTileGUI $parent, Tile $tile) {
		$this->tile = $tile;

		parent::__construct($parent);

		if (!self::access()->hasWriteAccess(self::tiles()->filterRefId())) {
			throw new ilException("You have no permission to access this page");
		}
	}


	/**
	 * @inheritdoc
	 */
	protected function getValue(/*string*/
		$key) {
		switch ($key) {
			case "image":
				if (!empty($this->tile->getImage())) {
					return "./" . ILIAS_WEB_DIR . "/" . CLIENT_ID . "/" . $this->tile->returnRelativeImagePath(true);
				}
				break;

			default:
				if (method_exists($this->tile, $method = "get" . $this->strToCamelCase($key))) {
					return $this->tile->{$method}($key);
				}
				if (method_exists($this->tile, $method = "is" . $this->strToCamelCase($key))) {
					return $this->tile->{$method}($key);
				}
		}

		return NULL;
	}


	/**
	 * @inheritdoc
	 */
	protected function initCommands()/*: void*/ {
		$this->addCommandButton(SrTileGUI::CMD_UPDATE_TILE, $this->txt("submit"), "tile_submit");

		$this->addCommandButton(SrTileGUI::CMD_CANCEL, $this->txt("cancel"), "tile_cancel");

		$this->setShowTopButtons(false);
	}


	/**
	 * @inheritdoc
	 */
	protected function initFields()/*: void*/ {
		try {
			$Notifications4Plugins = ilNotifications4PluginsPlugin::PLUGIN_NAME;
		} catch (Throwable $ex) {
			$Notifications4Plugins = "Notifications4Plugins";
		}

		$this->fields = [
			"tile_enabled" => [
				self::PROPERTY_CLASS => ilCheckboxInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_DISABLED => (self::tiles()->isTopTile($this->tile)
					|| ($parent_tile = self::tiles()->getParentTile($this->tile)) !== NULL
					&& $parent_tile->isTileEnabledChildren())
			],
			"tile_enabled_children" => [
				self::PROPERTY_CLASS => ilCheckboxInputGUI::class,
				self::PROPERTY_REQUIRED => false
			],

			"tile" => [
				self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class
			],
			"background_color_type" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::COLOR_TYPE_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::COLOR_TYPE_SET => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							"background_color" => [
								self::PROPERTY_CLASS => ilColorPickerInputGUI::class,
								self::PROPERTY_REQUIRED => false,
								"setDefaultColor" => ""
							]
						],
						"setTitle" => $this->txt("set")
					]
				],
				"setTitle" => $this->txt("background_color")
			],
			"margin_type" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::MARGIN_TYPE_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::MARGIN_TYPE_SET => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							"margin" => [
								self::PROPERTY_CLASS => ilNumberInputGUI::class,
								self::PROPERTY_REQUIRED => false,
								"setSuffix" => "px"
							]
						],
						"setTitle" => $this->txt("set")
					]
				],
				"setTitle" => $this->txt("margin")
			],

			"image_header" => [
				self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class,
				"setTitle" => $this->txt("image")
			],
			"image" => [
				self::PROPERTY_CLASS => ilImageFileInputGUI::class,
				self::PROPERTY_REQUIRED => false
			],
			"image_position" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::POSITION_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::POSITION_TOP => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("position_top")
					],
					Tile::POSITION_BOTTOM => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("position_bottom")
					]
				],
				"setTitle" => $this->txt("position")
			],
			"object_icon_position" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::POSITION_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::POSITION_NONE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_none")
					],
					Tile::POSITION_LEFT_TOP => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("position_left_top")
					],
					Tile::POSITION_LEFT_BOTTOM => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("position_left_bottom")
					],
					Tile::POSITION_RIGHT_TOP => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("position_right_top")
					],
					Tile::POSITION_RIGHT_BOTTOM => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("position_right_bottom")
					]
				]
			],

			"label" => [
				self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class
			],
			"show_title" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::SHOW_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::SHOW_FALSE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_false")
					],
					Tile::SHOW_TRUE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_true")
					]
				]
			],
			"font_color_type" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::COLOR_TYPE_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::COLOR_TYPE_CONTRAST => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("color_contrast")
					],
					Tile::COLOR_TYPE_SET => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							"font_color" => [
								self::PROPERTY_CLASS => ilColorPickerInputGUI::class,
								self::PROPERTY_REQUIRED => false,
								"setDefaultColor" => ""
							]
						],
						"setTitle" => $this->txt("set")
					]
				],
				"setTitle" => $this->txt("font_color")
			],
			"font_size_type" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::FONT_SIZE_TYPE_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::FONT_SIZE_TYPE_SET => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							"font_size" => [
								self::PROPERTY_CLASS => ilNumberInputGUI::class,
								self::PROPERTY_REQUIRED => false,
								"setSuffix" => "px"
							]
						],
						"setTitle" => $this->txt("set")
					]
				],
				"setTitle" => $this->txt("font_size")
			],
			"label_horizontal_align" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::HORIZONTAL_ALIGN_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::HORIZONTAL_ALIGN_LEFT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("horizontal_align_left")
					],
					Tile::HORIZONTAL_ALIGN_CENTER => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("horizontal_align_center")
					],
					Tile::HORIZONTAL_ALIGN_RIGHT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("horizontal_align_right")
					]
				],
				"setTitle" => $this->txt("horizontal_align")
			],
			"label_vertical_align" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::VERTICAL_ALIGN_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::VERTICAL_ALIGN_TOP => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("vertical_align_top")
					],
					Tile::VERTICAL_ALIGN_CENTER => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("vertical_align_center")
					],
					Tile::VERTICAL_ALIGN_BOTTOM => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("vertical_align_bottom")
					]
				],
				"setTitle" => $this->txt("vertical_align")
			],

			"actions" => [
				self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class
			],
			"actions_position" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::POSITION_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::POSITION_LEFT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("position_left")
					],
					Tile::POSITION_RIGHT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("position_right")
					]
				],
				"setTitle" => $this->txt("position")
			],
			"actions_vertical_align" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::VERTICAL_ALIGN_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::VERTICAL_ALIGN_TOP => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("vertical_align_top")
					],
					Tile::VERTICAL_ALIGN_CENTER => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("vertical_align_center")
					],
					Tile::VERTICAL_ALIGN_BOTTOM => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("vertical_align_bottom")
					]
				],
				"setTitle" => $this->txt("vertical_align")
			],
			"show_actions" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::SHOW_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::SHOW_FALSE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_false")
					],
					Tile::SHOW_TRUE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_true_if_permitted")
					]
				]
			],

			"favorites" => [
				self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class,
				self::PROPERTY_NOT_ADD => (!self::ilias()->favorites(self::dic()->user())->enabled())
			],
			"show_favorites_icon" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::SHOW_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::SHOW_FALSE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_false")
					],
					Tile::SHOW_TRUE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_true")
					]
				],
				self::PROPERTY_NOT_ADD => (!self::ilias()->favorites(self::dic()->user())->enabled())
			],

			"rating" => [
				self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class
			],
			"enable_rating" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::SHOW_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::SHOW_FALSE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("disabled")
					],
					Tile::SHOW_TRUE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("enabled")
					]
				]
			],
			"show_likes_count" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::SHOW_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::SHOW_FALSE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_false")
					],
					Tile::SHOW_TRUE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_true")
					]
				]
			],

			"recommendation" => [
				self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class,
				self::PROPERTY_NOT_ADD => empty($Notifications4Plugins)
			],
			"show_recommend_icon" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::SHOW_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::SHOW_FALSE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_false")
					],
					Tile::SHOW_TRUE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_true")
					]
				],
				self::PROPERTY_NOT_ADD => empty($Notifications4Plugins)
			],
			"recommend_mail_template_type" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::MAIL_TEMPLATE_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::MAIL_TEMPLATE_SET => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							"recommend_mail_template" => [
								self::PROPERTY_CLASS => ilSelectInputGUI::class,
								self::PROPERTY_REQUIRED => false,
								self::PROPERTY_OPTIONS => [ "" => "" ] + self::tiles()->getMailTemplatesText(),
								"setInfo" => $Notifications4Plugins
							]
						],
						"setTitle" => $this->txt("set")
					]
				],
				"setTitle" => $this->txt("recommend_mail_template"),
				self::PROPERTY_NOT_ADD => empty($Notifications4Plugins)
			],

			"learning_process" => [
				self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class,
				self::PROPERTY_NOT_ADD => self::ilias()->learningProgress(self::dic()->user())->enabled()
			],
			"show_learning_process" => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_REQUIRED => false,
				self::PROPERTY_SUBITEMS => [
					Tile::LEARNING_PROCCESS_PARENT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_NOT_ADD => self::tiles()->isTopTile($this->tile),
						"setTitle" => $this->txt("parent")
					],
					Tile::LEARNING_PROCCESS_NONE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_none")
					],
					Tile::LEARNING_PROCCESS_ICON => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_learning_process_icon")
					],
					Tile::LEARNING_PROCCESS_BAR => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						"setTitle" => $this->txt("show_learning_process_bar")
					]
				],
				self::PROPERTY_NOT_ADD => self::ilias()->learningProgress(self::dic()->user())->enabled()
			],
		];
	}


	/**
	 * @inheritdoc
	 */
	protected function initId()/*: void*/ {
		$this->setId("tile_form");
	}


	/**
	 * @inheritdoc
	 */
	protected function initTitle()/*: void*/ {
		$this->setTitle(self::plugin()->translate("object", self::LANG_MODULE, [ $this->tile->getProperties()->getTitle() ]));
	}


	/**
	 * @inheritdoc
	 */
	protected function storeValue(/*string*/
		$key, $value)/*: void*/ {
		switch ($key) {
			case "image":
				if (!self::dic()->upload()->hasBeenProcessed()) {
					self::dic()->upload()->process();
				}

				/** @var UploadResult $result */
				$result = array_pop(self::dic()->upload()->getResults());

				if ($this->getInput("image_delete") || $result->getSize() > 0) {
					if (!empty($this->tile->getImage())) {
						$image_path = ILIAS_WEB_DIR . "/" . CLIENT_ID . "/" . $this->tile->returnRelativeImagePath(true);
						if (file_exists($image_path)) {
							unlink($image_path);
						}
						$this->tile->setImage("");
					}
				}

				if (intval($result->getSize()) === 0) {
					break;
				}

				$file_name = $this->tile->getTileId() . "." . pathinfo($result->getName(), PATHINFO_EXTENSION);

				self::dic()->upload()->moveOneFileTo($result, $this->tile->returnRelativeImagePath(), Location::WEB, $file_name, true);

				$this->tile->setImage($file_name);
				break;

			case "tile_enabled":
				if ($this->getItemByPostVar($key)->getDisabled()) {
					$value = true;
				}

				$this->tile->setTileEnabled($value);
				break;

			default:
				if (method_exists($this->tile, $method = "set" . $this->strToCamelCase($key))) {
					$this->tile->{$method}($value);
				}
				break;
		}
	}


	/**
	 * @inheritdoc
	 */
	public function storeForm()/*: bool*/ {
		if (!parent::storeForm()) {
			return false;
		}

		$this->tile->store();

		return true;
	}


	/**
	 * @param string $string
	 *
	 * @return string
	 */
	protected function strToCamelCase($string): string {
		return str_replace("_", "", ucwords($string, "_"));
	}
}
