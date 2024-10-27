<?php
// 자동완성용 자소단위검색

// chr 유니코드버전 (3바이트전용)
function ucchr($uc)
{
	return chr(0xe0 | ($uc >> 12)) . chr(0x80 | (($uc & 0xfc0) >> 6)) . chr(0x80 | ($uc & 0x3f));
}

function ucTest($uc)
{
	return ($uc >> 12);
}

// ord 유니코드버전 (3바이트전용)
function ucord($uc)
{
	return (((ord($uc[0]) ^ 0xe0) << 12) | ((ord($uc[1]) ^ 0x80) << 6) | (ord($uc[2]) ^ 0x80));
}

// 각 모음에 해당되는 검색범위 리턴
function getse($c)
{
	switch($c)
	{
		case 'ㄱ': return array('가','깋'); case 'ㄲ': return array('까','낗');
		case 'ㄴ': return array('나','닣'); case 'ㄷ': return array('다','딯');
		case 'ㄸ': return array('따','띻'); case 'ㄹ': return array('라','맇');
		case 'ㅁ': return array('마','밓'); case 'ㅂ': return array('바','빟');
		case 'ㅃ': return array('빠','삫'); case 'ㅅ': return array('사','싷');
		case 'ㅆ': return array('싸','앃'); case 'ㅇ': return array('아','잏');
		case 'ㅈ': return array('자','짛'); case 'ㅉ': return array('짜','찧');
		case 'ㅊ': return array('차','칳'); case 'ㅋ': return array('카','킿');
		case 'ㅍ': return array('파','핗'); case 'ㅌ': return array('타','팋');
		case 'ㅎ': return array('하','힣');
	}

	return false;
}

function getACQuery($word, $target, $table, $fields, $limit = 15)
{
	$len = strlen($word);

	if (!$len)
		return false;

	// 끝글자가 영어가 아니면
	if (ord($word{$len - 1}) & 0x80)
	{

		// 끝에 한글자
		$lastchar = substr($word, -3);
		// 확인사살
		if ($lastchar === false || !(ord($lastchar{0}) & 0xe0))
			return false;

		// 끝에 한글자를 뺀 나머지
		$subword = substr($word, 0, -3);

		$se = getse($lastchar);
		// 모음만있을경우
		if ($se !== false)
		{
			$cdn = "({$target} between '{$subword}{$se[0]}' and '{$subword}{$se[1]}')";
		}
		// 아니면
		else
		{
			$uo = ucord($lastchar) - 0xac00;

			// 한글이 아냐 즐 (가~힣)
			if ($uo < 0 || $uo > 11172)
				return false;

			// 종성
			$hc = $uo % 28;

			// 종성이 존재하면
			// 닭 = 달ㄱ  값 = 갑ㅅ
			if ($hc > 0)
			{
				--$hc;

				$sub = 'ㄱㄱㅅㄴㅈㅎㄷㄹㄱㅁㅂㅅㅌㅍㅎㅁㅂㅅㅅㅆㅇㅈㅊㅋㅌㅍㅎ';
				$offset = array(1,2,2,4,1,2,7,8,1,2,3,4,5,6,7,16,17,1,19,20,21,22,23,24,25,26,27);

				// 종성분리 달 -> 다 (쌍받침이면 닭 -> 달)
				// 분리된거로 검색어생성
				$pc = ucchr($uo - $offset[$hc] + 0xac00);
				$se = getse(substr($sub, $hc * 3, 3));

echo("$uo - ");
echo("$offset[$hc] + ");
echo("$pc = ");

				// 닭, 값같은 글자땜에..
				switch ($hc)
				{
					// ㄱ은 건너뜀--;
					case 3: $offset2 = 2; break; // ㄴ
					case 7: $offset2 = 7; break; // ㄹ
					case 16: $offset2 = 1; break; // ㅂ
					default: $offset2 = 0;
				}

				if ($offset2 > 0)
				{
					$tmp = ucchr($uo + $offset2 + 0xac00);

					$cdn = "({$target} between '{$word}' and '{$subword}{$tmp}')";
				}
				else
				{
					$cdn = "({$target} like '{$word}%')";
				}

				$cdn2 = "({$target} between '{$subword}{$pc}{$se[0]}' and '{$subword}{$pc}{$se[1]}')";
			}
			// 종성이 없으면
			else
			{
				$base = $uo - $hc;

				// 중성에 따라 범위가 달라짐 (ㅜ,ㅞ 등)
				switch ((int)(($base % 588) / 28)) // 588 = 28 * 21
				{
					// ㅗ, ㅜ
					case 8:
					case 13: $offset = 111; break; // 28 * 4 - 1
					// ㅡ
					case 18: $offset = 55; break; // 28 * 2 - 1
					default: $offset = 27;
				}

				$en = ucchr($base + 0xac00 + $offset);

				$cdn = "({$target} between '{$word}' and '{$subword}{$en}')";
			}
		}
	}
	// 영어일때ㅠㅠ 베리심플
	else
	{
		$cdn = "{$target} like '{$word}%'";
	}

	$q1 = "select {$fields} from {$table} where";
	$q2 = "order by {$target} limit {$limit}";
	$query = "({$q1} {$cdn} {$q2})";
	if (isset($cdn2)) $query .= " union all ({$q1} {$cdn2} {$q2})";

	$query .= ' limit ' . $limit;

	return $query;
}

$result1 = ucchr( 65 );
echo( $result1 );
echo("<BR><BR>");

$result2 = ucord( 'abc' );
echo( $result2 );
echo("<BR>");

$test = "abcd";
echo( $test[0] );
echo("<BR>");

$arrstr = getse('ㅇ');
echo( $arrstr[0] . $arrstr[1] );
echo("<BR><BR>");

echo( getACQuery("워드", "타겟", "테이블", "필드") );
echo("<BR><BR>");
echo( getACQuery("닭값", "타겟", "테이블", "필드") );
echo("<BR><BR>");
echo( getACQuery("튀퉤", "타겟", "테이블", "필드") );

echo("<BR><BR>");
echo( ucTest(18 - 1 + 44032) );

?>

<html>

<head>
</head>

<body>
<br>
</body>

</html>
