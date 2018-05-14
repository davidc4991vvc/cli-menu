<?php

namespace PhpSchool\CliMenu\Builder;

use PhpSchool\CliMenu\Action\ExitAction;
use PhpSchool\CliMenu\Action\GoBackAction;
use PhpSchool\CliMenu\MenuItem\AsciiArtItem;
use PhpSchool\CliMenu\MenuItem\MenuItemInterface;
use PhpSchool\CliMenu\MenuItem\SelectableItem;
use PhpSchool\CliMenu\MenuItem\SplitItem;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\MenuStyle;
use PhpSchool\CliMenu\Terminal\TerminalFactory;
use PhpSchool\CliMenu\Util\ColourUtil;
use PhpSchool\Terminal\Terminal;
use RuntimeException;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 * @author Aydin Hassan <aydin@hotmail.com>
 */
class CliMenuBuilder implements Builder
{
    use BuilderUtils;
    
    /**
     * @var bool
     */
    private $isBuilt = false;

    /**
     * @var SplitItemBuilder[]
     */
    private $splitItemBuilders = [];

    /**
     * @var SplitItem[]
     */
    private $splitItems = [];

    /**
     * @var string
     */
    private $goBackButtonText = 'Go Back';

    /**
     * @var string
     */
    private $exitButtonText = 'Exit';

    /**
     * @var array
     */
    private $style;

    /**
     * @var Terminal
     */
    private $terminal;

    /**
     * @var string
     */
    private $menuTitle;

    /**
     * @var bool
     */
    private $disableDefaultItems = false;

    /**
     * @var bool
     */
    private $disabled = false;

    public function __construct(Builder $parent = null)
    {
        $this->parent   = $parent;
        $this->terminal = $this->parent !== null
            ? $this->parent->getTerminal()
            : TerminalFactory::fromSystem();
        $this->style = MenuStyle::getDefaultStyleValues();
    }

    public function setTitle(string $title) : self
    {
        $this->menuTitle = $title;

        return $this;
    }

    public function addMenuItem(MenuItemInterface $item) : self
    {
        $this->menuItems[] = $item;

        return $this;
    }

    public function addItems(array $items) : self
    {
        foreach ($items as $item) {
            $this->addItem(...$item);
        }

        return $this;
    }

    public function addAsciiArt(string $art, string $position = AsciiArtItem::POSITION_CENTER, string $alt = '') : self
    {
        $this->addMenuItem(new AsciiArtItem($art, $position, $alt));

        return $this;
    }

    /**
     * Add a split item
     */
    public function addSplitItem() : SplitItemBuilder
    {
        $this->menuItems[] = $id = uniqid('splititem-placeholder-', true);
        
        $this->splitItemBuilders[$id] = new SplitItemBuilder($this);
        return $this->splitItemBuilders[$id];
    }

    /**
     * Disable a submenu
     *
     * @throws \InvalidArgumentException
     */
    public function disableMenu() : self
    {
        if (!$this->parent) {
            throw new \InvalidArgumentException(
                'You can\'t disable the root menu'
            );
        }

        $this->disabled = true;

        return $this;
    }

    public function isMenuDisabled() : bool
    {
        return $this->disabled;
    }

    public function setGoBackButtonText(string $goBackButtonTest) : self
    {
        $this->goBackButtonText = $goBackButtonTest;

        return $this;
    }

    public function setExitButtonText(string $exitButtonText) : self
    {
        $this->exitButtonText = $exitButtonText;

        return $this;
    }

    public function setBackgroundColour(string $colour, string $fallback = null) : self
    {
        $this->style['bg'] = ColourUtil::validateColour(
            $this->terminal,
            $colour,
            $fallback
        );

        return $this;
    }

    public function setForegroundColour(string $colour, string $fallback = null) : self
    {
        $this->style['fg'] = ColourUtil::validateColour(
            $this->terminal,
            $colour,
            $fallback
        );

        return $this;
    }

    public function setWidth(int $width) : self
    {
        $this->style['width'] = $width;

        return $this;
    }

    public function setPadding(int $topBottom, int $leftRight = null) : self
    {
        if ($leftRight === null) {
            $leftRight = $topBottom;
        }

        $this->setPaddingTopBottom($topBottom);
        $this->setPaddingLeftRight($leftRight);

        return $this;
    }

    public function setPaddingTopBottom(int $topBottom) : self
    {
        $this->style['paddingTopBottom'] = $topBottom;

        return $this;
    }

    public function setPaddingLeftRight(int $leftRight) : self
    {
        $this->style['paddingLeftRight'] = $leftRight;

        return $this;
    }

    public function setMarginAuto() : self
    {
        $this->style['marginAuto'] = true;

        return $this;
    }

    public function setMargin(int $margin) : self
    {
        $this->style['marginAuto'] = false;
        $this->style['margin'] = $margin;

        return $this;
    }

    public function setUnselectedMarker(string $marker) : self
    {
        $this->style['unselectedMarker'] = $marker;

        return $this;
    }

    public function setSelectedMarker(string $marker) : self
    {
        $this->style['selectedMarker'] = $marker;

        return $this;
    }

    public function setItemExtra(string $extra) : self
    {
        $this->style['itemExtra'] = $extra;

        return $this;
    }

    public function setTitleSeparator(string $separator) : self
    {
        $this->style['titleSeparator'] = $separator;

        return $this;
    }

    public function setBorder(
        int $topWidth,
        $rightWidth = null,
        $bottomWidth = null,
        $leftWidth = null,
        string $colour = null
    ) : self {
        if (!is_int($rightWidth)) {
            $colour = $rightWidth;
            $rightWidth = $bottomWidth = $leftWidth = $topWidth;
        } elseif (!is_int($bottomWidth)) {
            $colour = $bottomWidth;
            $bottomWidth = $topWidth;
            $leftWidth = $rightWidth;
        } elseif (!is_int($leftWidth)) {
            $colour = $leftWidth;
            $leftWidth = $rightWidth;
        }

        $this->style['borderTopWidth'] = $topWidth;
        $this->style['borderRightWidth'] = $rightWidth;
        $this->style['borderBottomWidth'] = $bottomWidth;
        $this->style['borderLeftWidth'] = $leftWidth;

        if (is_string($colour)) {
            $this->style['borderColour'] = $colour;
        } elseif ($colour !== null) {
            throw new \InvalidArgumentException('Invalid colour');
        }

        return $this;
    }

    public function setBorderTopWidth(int $width) : self
    {
        $this->style['borderTopWidth'] = $width;
        
        return $this;
    }

    public function setBorderRightWidth(int $width) : self
    {
        $this->style['borderRightWidth'] = $width;

        return $this;
    }

    public function setBorderBottomWidth(int $width) : self
    {
        $this->style['borderBottomWidth'] = $width;

        return $this;
    }

    public function setBorderLeftWidth(int $width) : self
    {
        $this->style['borderLeftWidth'] = $width;

        return $this;
    }

    public function setBorderColour(string $colour, $fallback = null) : self
    {
        $this->style['borderColour'] = $colour;
        $this->style['borderColourFallback'] = $fallback;

        return $this;
    }

    public function setTerminal(Terminal $terminal) : self
    {
        $this->terminal = $terminal;
        return $this;
    }

    public function getTerminal() : Terminal
    {
        return $this->terminal;
    }

    private function getDefaultItems() : array
    {
        $actions = [];
        if ($this->parent) {
            $actions[] = new SelectableItem($this->goBackButtonText, new GoBackAction);
        }

        $actions[] = new SelectableItem($this->exitButtonText, new ExitAction);
        return $actions;
    }

    public function disableDefaultItems() : self
    {
        $this->disableDefaultItems = true;

        return $this;
    }

    private function itemsHaveExtra(array $items) : bool
    {
        return !empty(array_filter($items, function (MenuItemInterface $item) {
            return $item->showsItemExtra();
        }));
    }

    /**
     * Recursively drop back to the parents menu style
     * when the current menu has a parent and has no changes
     */
    public function getMenuStyle() : MenuStyle
    {
        if (null === $this->parent) {
            return $this->buildStyle();
        }

        if ($this->style !== MenuStyle::getDefaultStyleValues()) {
            return $this->buildStyle();
        }

        return $this->parent->getMenuStyle();
    }

    private function buildStyle() : MenuStyle
    {
        $style = (new MenuStyle($this->terminal))
            ->setFg($this->style['fg'])
            ->setBg($this->style['bg'])
            ->setWidth($this->style['width'])
            ->setPaddingTopBottom($this->style['paddingTopBottom'])
            ->setPaddingLeftRight($this->style['paddingLeftRight'])
            ->setSelectedMarker($this->style['selectedMarker'])
            ->setUnselectedMarker($this->style['unselectedMarker'])
            ->setItemExtra($this->style['itemExtra'])
            ->setDisplaysExtra($this->style['displaysExtra'])
            ->setTitleSeparator($this->style['titleSeparator'])
            ->setBorderTopWidth($this->style['borderTopWidth'])
            ->setBorderRightWidth($this->style['borderRightWidth'])
            ->setBorderBottomWidth($this->style['borderBottomWidth'])
            ->setBorderLeftWidth($this->style['borderLeftWidth'])
            ->setBorderColour($this->style['borderColour'], $this->style['borderColourFallback']);

        $this->style['marginAuto'] ? $style->setMarginAuto() : $style->setMargin($this->style['margin']);

        return $style;
    }

    /**
     * @throws RuntimeException
     */
    public function getSubMenu(string $id) : CliMenu
    {
        if (false === $this->isBuilt) {
            throw new RuntimeException(sprintf('Menu: "%s" cannot be retrieved until menu has been built', $id));
        }

        return $this->subMenus['submenu-placeholder-' . $id];
    }
    
    private function buildSplitItems(array $items) : array
    {
        return array_map(function ($item) {
            if (!is_string($item) || 0 !== strpos($item, 'splititem-placeholder-')) {
                return $item;
            }

            $splitItemBuilder        = $this->splitItemBuilders[$item];
            $this->splitItems[$item] = $splitItemBuilder->build();

            return $this->splitItems[$item];
        }, $items);
    }

    public function build() : CliMenu
    {
        $this->isBuilt = true;

        $mergedItems = $this->disableDefaultItems
            ? $this->menuItems
            : array_merge($this->menuItems, $this->getDefaultItems());

        
        $menuItems = $this->buildSplitItems($mergedItems);
        $menuItems = $this->buildSubMenus($menuItems);

        $this->style['displaysExtra'] = $this->itemsHaveExtra($menuItems);

        $menu = new CliMenu(
            $this->menuTitle,
            $menuItems,
            $this->terminal,
            $this->getMenuStyle()
        );

        foreach ($this->subMenus as $subMenu) {
            $subMenu->setParent($menu);
        }
        
        foreach ($this->splitItemBuilders as $splitItemBuilder) {
            $splitItemBuilder->setSubMenuParents($menu);
        }

        return $menu;
    }
}