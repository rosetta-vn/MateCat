<?php

/**
 * @group regression
 * @covers CatUtils::layer2ToLayer0
 * this battery of tests sends one string in input as $source_segment to CatUtils::view2rawxliff method and
 * verifies that the output is equal to the $expected_segment.
 * User: dinies
 * Date: 30/03/16
 * Time: 17.25
 */
class View2rawxliffTest extends AbstractTest
{
protected $source_segment;
    protected $expected_segment;
    /**
     * @group regression
     * @covers CatUtils::layer2ToLayer0
     */
    public function testview2rawxliff_with_emoticons()
    {
        $this->source_segment = <<<'LAB'
Modulo  ##$_09$##😆LII-P😆  S-2RI##$_0A$##P😆 1415😆
LAB;
        $this->expected_segment = <<<'LAB'
Modulo  	&#128518;LII-P&#128518;  S-2RI
P&#128518; 1415&#128518;
LAB;
        self::assertEquals($this->expected_segment, CatUtils::layer2ToLayer0($this->source_segment));
    }

    /**
     * @group regression
     * @covers CatUtils::layer2ToLayer0
     */
    public function testview2rawxliff_with_tabulations_and_new_lines()
    {
        $this->source_segment = <<<'LAB'
Modulo  ##$_09$##😆LII-P😆  S-2RI##$_0A$##P😆 1415😆
LAB;
        $this->expected_segment = <<<'LAB'
Modulo  	&#128518;LII-P&#128518;  S-2RI
P&#128518; 1415&#128518;
LAB;
        self::assertEquals($this->expected_segment, CatUtils::layer2ToLayer0($this->source_segment));
    }

    /**
     * @group regression
     * @covers CatUtils::layer2ToLayer0
     */
    public function testview2rawxliff()
    {
        $this->source_segment = <<<'LAB'
<g id="1">􀁸</g><g id="2"> </g><g id="3">Salon salle à manger appartement invité n ° 1 [3-1-03]</g>
LAB;
        $this->expected_segment = <<<'LAB'
<g id="1">&#1048696;</g><g id="2"> </g><g id="3">Salon salle à manger appartement invité n ° 1 [3-1-03]</g>
LAB;
        self::assertEquals($this->expected_segment, CatUtils::layer2ToLayer0($this->source_segment));
    }

    /**
     * @group regression
     * @covers CatUtils::layer2ToLayer0
     */
    public function testview2rawxliff_no_alterations()
    {
        $this->source_segment = <<<'LAB'
<g id="1">3.2.122 M121 - B</g><g id="2">LOC PORTE A PANNEAUX IT EBENISTERIE ONU VANTAIL </g><g id="3">- </g><g id="4">VERNIS ET</g>
LAB;
        $this->expected_segment = <<<'LAB'
<g id="1">3.2.122 M121 - B</g><g id="2">LOC PORTE A PANNEAUX IT EBENISTERIE ONU VANTAIL </g><g id="3">- </g><g id="4">VERNIS ET</g>
LAB;
        self::assertEquals($this->expected_segment, CatUtils::layer2ToLayer0($this->source_segment));
    }

    /**
     * @group regression
     * @covers CatUtils::layer2ToLayer0
     */
    public function testview2rawxliff_no_alterations_2()
    {
        $this->source_segment = <<<'LAB'
<g id="1">3.2.126 M125 E</g><g id="2">NSEMBLE PLAN DE LAVABO ET ARMOIRE DE TOILETTE LUMINEUSE</g>
LAB;
        $this->expected_segment = <<<'LAB'
<g id="1">3.2.126 M125 E</g><g id="2">NSEMBLE PLAN DE LAVABO ET ARMOIRE DE TOILETTE LUMINEUSE</g>
LAB;
        self::assertEquals($this->expected_segment, CatUtils::layer2ToLayer0($this->source_segment));
    }

    /**
     * @group regression
     * @covers CatUtils::layer2ToLayer0
     */
    public function testview2rawxliff_high_encoded_char_1()
    {
        $this->source_segment = <<<'LAB'
<g id="1">􀂾</g><g id="2"> </g><g id="3">Bâtiment 3</g>
LAB;
        $this->expected_segment = <<<'LAB'
<g id="1">&#1048766;</g><g id="2"> </g><g id="3">Bâtiment 3</g>
LAB;
        self::assertEquals($this->expected_segment, CatUtils::layer2ToLayer0($this->source_segment));
    }

    /**
     * @group regression
     * @covers CatUtils::layer2ToLayer0
     */
    public function testview2rawxliff_high_encoded_char_2()
    {
        $this->source_segment = <<<'LAB'
<g id="1">􀂾</g><g id="2"> </g><g id="3">D'une ossature à échelle réalisée en bois dur de section appropriate Traité fongicide insecticide with</g>
LAB;
        $this->expected_segment = <<<'LAB'
<g id="1">&#1048766;</g><g id="2"> </g><g id="3">D'une ossature à échelle réalisée en bois dur de section appropriate Traité fongicide insecticide with</g>
LAB;
        self::assertEquals($this->expected_segment, CatUtils::layer2ToLayer0($this->source_segment));
    }
}