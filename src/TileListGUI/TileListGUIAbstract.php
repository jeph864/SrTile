<?php

namespace srag\Plugins\SrTile\TileListGUI;

use ilSrTilePlugin;
use srag\DIC\SrTile\DICTrait;
use srag\Plugins\SrTile\LearningProgress\LearningProgressFilterGUI;
use srag\Plugins\SrTile\LearningProgress\LearningProgressLegendGUI;
use srag\Plugins\SrTile\Tile\Tile;
use srag\Plugins\SrTile\TileGUI\TileGUIInterface;
use srag\Plugins\SrTile\TileList\TileListInterface;
use srag\Plugins\SrTile\Utils\SrTileTrait;

/**
 * Class TileListContainerGUI
 *
 * @package srag\Plugins\SrTile\TileListGUI
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author  studer + raimann ag - Martin Studer <ms@studer-raimann.ch>
 */
abstract class TileListGUIAbstract implements TileListGUIInterface {

	use SrTileTrait;
	use DICTrait;
	const PLUGIN_CLASS_NAME = ilSrTilePlugin::class;
	/**
	 * @var TileListInterface $tile_list
	 */
	protected $tile_list;


	/**
	 * TileListGUIAbstract constructor
	 *
	 * @param mixed $param
	 */
	public function __construct($param) {
		$list_class = static::LIST_CLASS;

		$this->tile_list = $list_class::getInstance($param);
	}


	/**
	 *
	 */
	protected function initJS()/*: void*/ {
		self::dic()->mainTemplate()->addJavaScript(self::plugin()->directory() . "/node_modules/@iconfu/svg-inject/dist/svg-inject.min.js");
	}


	/**
	 * @inheritdoc
	 */
	public function render(): string {
		$this->initJS();

		$tile_list_html = "";

		if (count($this->tile_list->getTiles()) > 0) {

			$parent_tile = self::tiles()->getInstanceForObjRefId(self::tiles()->filterRefId() ?? ROOT_FOLDER_ID);

			self::dic()->mainTemplate()->addCss(self::plugin()->directory() . "/css/srtile.css");

			$tpl = self::plugin()->template("TileList/tile_list.html");

			$tpl->setVariable("VIEW", $parent_tile->getView());

			$gui_class = static::GUI_CLASS;
			$tile_html = self::output()->getHTML(array_map(function (Tile $tile) use ($gui_class): TileGUIInterface {
				return new $gui_class($tile);
			}, $this->tile_list->getTiles()));

			$tpl->setVariable("TILES", $tile_html);

			if (!self::dic()->ctrl()->isAsynch() && $parent_tile->getShowLearningProgressFilter() === Tile::SHOW_TRUE) {
				(new LearningProgressFilterGUI())->initToolbar();
			}

			if (!self::dic()->ctrl()->isAsynch() && $parent_tile->getShowLearningProgressLegend() === Tile::SHOW_TRUE) {
				$tpl->setVariable("LP_LEGEND", self::output()->getHTML(new LearningProgressLegendGUI()));
			}

			$tile_list_html = self::output()->getHTML($tpl);

			$this->hideOriginalRowsOfTiles();
		}

		return $tile_list_html;
	}


	/**
	 * @inheritdoc
	 */
	public function hideOriginalRowsOfTiles() /*: void*/ {
		$css = '';

		$parent_tile = self::tiles()->getInstanceForObjRefId(self::tiles()->filterRefId() ?? ROOT_FOLDER_ID);

		$css .= '.tile';
		$css .= '{' . $parent_tile->_getLayout() . '}';

		$is_parent_css_rendered = false;
		foreach ($this->tile_list->getTiles() as $tile) {
			self::dic()->appEventHandler()->raise("Plugins/" . ilSrTilePlugin::PLUGIN_NAME, ilSrTilePlugin::EVENT_CHANGE_TILE_BEFORE_RENDER, [
				"tile" => $tile
			]);

			$css .= '#sr_tile_' . $tile->getTileId();
			$css .= '{' . $tile->_getSize() . '}';

			$css .= '#sr_tile_' . $tile->getTileId() . ' .card_bottom';
			$css .= '{' . $tile->_getColor(false, true) . '}';

			$css .= '#sr_tile_' . $tile->getTileId() . ' > .card';
			$css .= '{' . $tile->_getColor() . $tile->_getBorder() . '}';

			$css .= '#sr_tile_' . $tile->getTileId() . ' .btn-default, ';
			$css .= '#sr_tile_' . $tile->getTileId() . ' .badge';
			$css .= '{' . $tile->_getColor(true) . '}';

			if (!$is_parent_css_rendered) {
				$is_parent_css_rendered = true;

				if ($parent_tile->getApplyColorsToGlobalSkin() === Tile::SHOW_TRUE) {
					if (!empty($parent_tile->_getBackgroundColor())) {
						$css .= 'a#il_mhead_t_focus';
						$css .= '{color:rgb(' . $parent_tile->_getBackgroundColor() . ')!important;}';
					}

					$css .= '.btn-default';
					$css .= '{' . $tile->_getColor();
					if (!empty($parent_tile->_getBackgroundColor())) {
						$css .= 'border-color:rgb(' . $parent_tile->_getBackgroundColor() . ')!important;';
					}
					$css .= '}';
				}
			}
		}

		self::dic()->mainTemplate()->addInlineCss($css);
	}
}
