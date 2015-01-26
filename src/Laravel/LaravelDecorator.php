<?php
class LaravelDecorator {
	public static function decorate(CodeFormatter &$fmt) {
		$fmt->removePass('AlignEquals');
		$fmt->removePass('AlignDoubleArrow');
		$fmt->removePass('AddMissingCurlyBraces');
		$fmt->addPass(new NamespaceMergeWithOpenTag());
		$fmt->addPass(new AllmanStyleBraces());
		// $fmt->addPass(new AllmanStyleBracesByPienGeng());
		$fmt->addPass(new RTrim());
		$fmt->addPass(new TightConcat());
		$fmt->addPass(new NoSpaceBeforeParenthesisClose());
		$fmt->addPass(new NoSpaceBetweenFunctionAndBracket());
		$fmt->addPass(new SpaceAroundExclamationMark());
		// $fmt->addPass(new SpaceAroundDot()); // just to standby in case needed one day.
		$fmt->addPass(new NoneDocBlockMinorCleanUp());
		$fmt->addPass(new SortUseNameSpace());
		$fmt->addPass(new AlignEqualsByConsecutiveBlocks());
		$fmt->addPass(new SpaceBetweenBracketVsVariableReturnBreakString());
		$fmt->addPass(new KeepEmptyCurlyBraces());
		// $fmt->addPass(new KeepSingleLineNonEmptyCurlyBraces());	// do not use this, failing miserably
	}
}