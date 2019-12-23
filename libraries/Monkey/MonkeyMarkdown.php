<?php

define('MDTKN_NULL', 0);
define('MDTKN_BOLD', 1);
define('MDTKN_UNDERLINE', 2);
define('MDTKN_ITALIC', 3);
define('MDTKN_OL', 4);
define('MDTKN_UL', 5);
define('MDTKN_LI', 6);
define('MDTKN_LINK', 7);
define('MDTKN_IMAGE', 8);
define('MDTKN_HEADER', 9);
define('MDTKN_BLOCKQUOTE', 10);
define('MDTKN_COLOR_TEXT', 11);
define('MDTKN_BACKGROUND_COLOR', 12);
define('MDTKN_TEXT_SIZE', 13);
define('MDTKN_MULTI_TEXT', 14);
define('MDTKN_RAW_TEXT', 15);
define('MDTKN_CODEBLOCK', 16);

define('MDTKN_LB_LINK', 17);
define('MDTKN_LB_IMAGE', 18);
define('MDTKN_RB', 19);

define('MDTKN_YOUTUBE', 25);

define('MDTKN_STRIKE', 30); // added much later

define('MD_LINK_REGEX', '$(?:\b(?:https?|s?ftp):(?:/|&#47;){2,3}[-A-Za-z0-9+&@#/%?=~_|!:,.;\ ]*[A-Za-z0-9+&@#/%=~_|])$');
define('MD_LINK_LINE_REGEX', '$\A(?:(.*?)\ )?(\b(?:https?|s?ftp):(?:/|&#47;){2,3}[-A-Za-z0-9+&@#/%?=~_|!:,.;\ ]*[A-Za-z0-9+&@#/%=~_|])\z$');

define('MD_FILTER', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789%!+=-|:;.,?$^() ');
define('MD_YOUTUBE_FILTER', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789%_-');

class MarkdownToken
{
	public $value, $type, $valid;
	public $parent;
	
	/**
	 * 
	 * @var MonkeyStack
	 */
	public $children;

	public function __construct( $value, $type, $parent = null, $valid = false )
	{
		$this->children = new MonkeyStack();
		$this->Set($value, $type);
		$this->parent = $parent;
		$this->valid = $valid;
	}

	public function Set( $value, $type )
	{
		$this->value = $value;
		$this->type = $type;
	}
	
	public function HasParent()
	{
		return $this->parent !== null;
	}
	
	public function GetDebug()
	{
		$out = $this->value;
		
		if ( $this->valid )
			$out .= " : Valid Token";
		else
			$out .= " : Invalid Token";
		
		echo $out . '<br />';
		
		$children = $this->children->Objects();
		
		foreach ( $children as &$child )
		{
			$child->GetDebug();
		}
	}
	
	public function GetFormattedText()
	{
		$tokens = $this->children->Objects();
		$output = '';
		
		foreach ( $tokens as &$token )
		{
			switch ( $token->type )
			{
				case MDTKN_ITALIC:
					if ( $token->valid )
					{
						$output .= '<';
						
						if ( !$token->value )
							$output .= '/';
						
						$output .= 'i>';
					}
					$output .= $token->GetFormattedText();
					break;
				case MDTKN_BOLD:
					if ( $token->valid )
					{
						$output .= '<';
					
						if ( !$token->value )
							$output .= '/';
					
						$output .= 'b>';
					}
					$output .= $token->GetFormattedText();
					break;
				case MDTKN_UNDERLINE:
					if ( $token->valid )
					{
						$output .= '<';
					
						if ( !$token->value )
							$output .= '/';
					
						$output .= 'u>';
					}
					$output .= $token->GetFormattedText();
					break;
				case MDTKN_STRIKE:
					if ( $token->valid )
					{
						$output .= '<';
							
						if ( !$token->value )
							$output .= '/';
							
						$output .= 's>';
					}
					$output .= $token->GetFormattedText();
					break;
				case MDTKN_LINK:
					if ( $token->valid && $token->value )
					{
						$data = $token->GetFormattedText();
						
						$text = preg_replace(MD_LINK_LINE_REGEX, '$1', $data);
						$link = preg_replace(MD_LINK_LINE_REGEX, '$2', $data);
						
						if ( $text !== $data && $link !== '' )
						{
							if ( $text === '' )
								$text = $link;
							
							$output .= '<a href="' . $link . '">' . $text . '</a>';
						}
						else
						{
							$output .= $data;
						}
					}
					break;
				case MDTKN_IMAGE:
					if ( $token->valid && $token->value )
					{
						$data = $token->GetFormattedText();
				
						$text = preg_replace(MD_LINK_LINE_REGEX, '$1', $data);
						$link = preg_replace(MD_LINK_LINE_REGEX, '$2', $data);
				
						if ( $text !== $data && $link !== '' )
						{
							$text = FilterText($text, MD_FILTER);
								
							$output .= '<img src="' . $link . '" alt="' . $text . '" title="' . $text . '" />';
						}
						else
						{
							$output .= $data;
						}
					}
					break;
				case MDTKN_COLOR_TEXT:
					if ( $token->valid )
					{
						$data = $token->GetFormattedText();
						
						static $colors = null;
						if ( $colors === null )
						{
							$colors = new MonkeyMap();
							
							$colors->Set('green', 'green');
							$colors->Set('red', 'red');
							$colors->Set('blue', 'blue');
							$colors->Set('black', '#000');
							$colors->Set('white', '#FFF');
						}
						
						$color = trim(@preg_replace('$\A(?:(.+?)\ )(?:.*)$', '$1', $data));
						$text = trim(@substr($data, strlen($color) + 1));
						
						if ( $colors->Contains($color) )
						{
							if ( $text !== '' )
								$output .= '<span style="color:' . $colors->Get($color) . ';">' . $text . '</span>';
						}
						elseif ( preg_match('$\A(?:[0-9]{0,3},){2}[0-9]{0,3}\z$', $color) )
						{
							if ( $text !== '' )
							{
								$rgb = explode(',', $color);
								
								$rgb[0] = intval($rgb[0]) % 256;
								$rgb[1] = intval($rgb[1]) % 256;
								$rgb[2] = intval($rgb[2]) % 256;
								
								$output .= '<span style="color:rgb(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ');">' . $text . '</span>';
							}
						}
						elseif ( preg_match('$\A([0-9A-F]{3}){1,2}\z$i', $color) )
						{
							if ( $text !== '' )
								$output .= '<span style="color:#' . strtoupper($color) . ';">' . $text . '</span>';
						}
						else $output .= $data;
					}
					else $output .= $token->GetFormattedText();
					break;
				case MDTKN_TEXT_SIZE:
					if ( $token->valid )
					{
						$data = $token->GetFormattedText();
						
						$size = trim(@preg_replace('$\A(?:([0-9]{1,3})\ )(?:.*)$', '$1', $data));
						$text = trim(@substr($data, strlen($size) + 1));
						
						if ( is_numeric($size) )
						{
							if ( $text !== '' )
							{
								$size = intval($size);
								
								if ( $size < 4 ) $size = 4;
								elseif ( $size > 128 ) $size = 128;
								
								$output .= '<span style="font-size:' . $size . 'px;">' . $text . '</span>';
							}
						}
						else $output .= $data;
					}
					else $output .= $token->GetFormattedText();
					break;
				case MDTKN_RAW_TEXT:
					$output .= $token->value;
					break;
				case MDTKN_YOUTUBE:
					if ( $token->valid )
						$output .= '<iframe width="640" height="360" src="https://www.youtube-nocookie.com/embed/' .
							FilterText(substr($token->GetFormattedText(), 0, 16), MD_YOUTUBE_FILTER) . '?rel=0" frameborder="0" allowfullscreen></iframe>';
					break;
			}
		}
		
		return $output;
	}
}

function MarkdownCleanupURL( $url )
{
	return str_replace(array('&#47;', '&amp;'), array('/', '&'), $url);
}









function MarkdownSimpleText( $text )
{
	global $filter;
	
	$root = new MarkdownToken('@ROOT', 0, null);
	$token = $root;
	
	$links = 0;
	$match = array(array('', 0));
	$addresses = array();
	
	while ( preg_match(MD_LINK_REGEX, $text, $match, PREG_OFFSET_CAPTURE, $match[0][1]) )
	{
		$addresses[] = MarkdownCleanupURL($match[0][0]);
		$text = preg_replace(MD_LINK_REGEX, '<<' . $links++, $text, 1);
	}
	
	for ( $i = 0; $i < strlen($text); $i++ )
	{
		$char = substr($text, $i, 1);
		$nextChar = substr($text, $i + 1, 1);
		
		if ( $token->type !== MDTKN_IMAGE && $char === "'" && $nextChar === "'" )
		{
			if ( $token->type === MDTKN_ITALIC )
			{
				$token->valid = true;
				$token = $token->parent;
				$token->children->Push(new MarkdownToken(0, MDTKN_ITALIC, $token, true));
			}
			else
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_ITALIC, $token));
				$token = $token->children->Top();
			}
			
			$i++;
		}
		elseif ( $token->type !== MDTKN_IMAGE && $char === '*' && $nextChar === '*' )
		{
			if ( $token->type === MDTKN_BOLD )
			{
				$token->valid = true;
				$token = $token->parent;
				$token->children->Push(new MarkdownToken(0, MDTKN_BOLD, $token, true));
			}
			else
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_BOLD, $token));
				$token = $token->children->Top();
			}
			
			$i++;
		}
		elseif ( $token->type !== MDTKN_IMAGE && $char === '-' && $nextChar === '-' )
		{
			if ( $token->type === MDTKN_STRIKE )
			{
				$token->valid = true;
				$token = $token->parent;
				$token->children->Push(new MarkdownToken(0, MDTKN_STRIKE, $token, true));
			}
			else
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_STRIKE, $token));
				$token = $token->children->Top();
			}
				
			$i++;
		}
		elseif ( $token->type !== MDTKN_IMAGE && $char === '_' && $nextChar === '_' )
		{
			if ( $token->type === MDTKN_UNDERLINE )
			{
				$token->valid = true;
				$token = $token->parent;
				$token->children->Push(new MarkdownToken(0, MDTKN_UNDERLINE, $token, true));
			}
			else
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_UNDERLINE, $token));
				$token = $token->children->Top();
			}
			
			$i++;
		}
		elseif ( $char === '<' && $nextChar === '<' )
		{
			$num = preg_replace('$([0-9]{1,2})(?:.*)$', '$1', substr($text, ++$i + 1, 2));
			$i += strlen($num);
			
			$token->children->Push(new MarkdownToken($addresses[intval($num)], MDTKN_RAW_TEXT, $token, true));
		}
		elseif ( $token->type !== MDTKN_IMAGE && $char === '['  )
		{
			if ( $nextChar === '!' )
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_IMAGE, $token));
				$token = $token->children->Top();
				$i++;
			}
			elseif ( $nextChar === ':' )
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_COLOR_TEXT, $token));
				$token = $token->children->Top();
				$i++;
			}
			elseif ( $nextChar === '+' )
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_TEXT_SIZE, $token));
				$token = $token->children->Top();
				$i++;
			}
			elseif ( $nextChar === '?' )
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_YOUTUBE, $token));
				$token = $token->children->Top();
				$i++;
			}
			else
			{
				$token->children->Push(new MarkdownToken(1, MDTKN_LINK, $token));
				$token = $token->children->Top();
				if ( $nextChar === '#' ) $i++;
			}
		}
		elseif ( $char === ']' )
		{
			if ( $token->type === MDTKN_LINK || $token->type === MDTKN_IMAGE || $token->type === MDTKN_COLOR_TEXT ||
				 $token->type === MDTKN_TEXT_SIZE || $token->type === MDTKN_YOUTUBE )
			{
				$token->valid = true;
				$token = $token->parent;
			}
		}
		else
		{
			$start = $i;
			
			if ( $token->type !== MDTKN_IMAGE )
			{
				while ( !(($char === '_' && $nextChar === '_') || ($char === "'" && $nextChar === "'") ||
						  ($char === '*' && $nextChar === '*') || ($char === '<' && $nextChar === '<') ||
						  ($char === '-' && $nextChar === '-') || $char === '[' || $char === ']' ) )
				{
					if ( ++$i >= strlen($text) )
						break;
	
					$char = substr($text, $i, 1);
					$nextChar = substr($text, $i + 1, 1);
				}
			}
			else
			{
				while ( !($char === ']' || ($char === '<' && $nextChar === '<')) )
				{
					if ( ++$i >= strlen($text) )
						break;
					
					$char = substr($text, $i, 1);
					$nextChar = substr($text, $i + 1, 1);
				}
			}
			
			$token->children->Push(new MarkdownToken(substr($text, $start, $i - $start), MDTKN_RAW_TEXT, $token, true));
			$i--;
		}
	}
	
	return $root->GetFormattedText();
}







function MarkdownText( $text )
{
	$text = preg_replace('$(\r\n|\r)$', "\n", $text);

	$lines = preg_split('$\n$', $text);

	$tokenStack = new MonkeyStack();
	$markdownList = new MonkeyList();
	$tokenStack->Push(MDTKN_NULL);

	$token = MDTKN_NULL;

	for ( $i = 0; $i < count($lines); $i++ )
	{
		$originalLine = $lines[$i];
		$line = trim($lines[$i]);
		
		if ( substr($originalLine, 0, 4) === '    ' )
		{
			$markdownList->AddLast(new MarkdownToken('', MDTKN_RAW_TEXT));
			
			while ( substr($originalLine, 0, 4) === '    ' )
			{
				$markdownList->AddLast(new MarkdownToken($line . "\n", MDTKN_MULTI_TEXT));
				
				if ( ++$i >= count($lines) ) break;
				
				$originalLine = $lines[$i];
				$line = substr($lines[$i], 4);
			}
				
			$markdownList->AddLast(new MarkdownToken('@codeblock', MDTKN_CODEBLOCK));
		}
		elseif ( substr($line, 0, 1) === '*' && (substr($line, 1, 1) !== '*' || substr($line, 1, 2) === '**') )
		{
			$markdownList->AddLast(new MarkdownToken('', MDTKN_RAW_TEXT));
				
			while ( substr($line, 0, 1) === '*' && (substr($line, 1, 1) !== '*' || substr($line, 1, 2) === '**') )
			{
				$markdownList->AddLast(new MarkdownToken(MarkdownSimpleText(ltrim(substr($line, 1))), MDTKN_MULTI_TEXT));
				$markdownList->AddLast(new MarkdownToken('@li-asterisk', MDTKN_LI));

				if ( ++$i >= count($lines) ) break;
				$line = trim($lines[$i]);
			}
				
			$i--;
			$markdownList->AddLast(new MarkdownToken('@ul-asterisk', MDTKN_UL));
		}
		elseif ( substr($line, 0, 1) === '+' )
		{
			$markdownList->AddLast(new MarkdownToken('', MDTKN_RAW_TEXT));

			while ( substr($line, 0, 1) === '+' )
			{
				$markdownList->AddLast(new MarkdownToken(MarkdownSimpleText(ltrim(substr($line, 1))), MDTKN_MULTI_TEXT));
				$markdownList->AddLast(new MarkdownToken('@li-plus', MDTKN_LI));

				if ( ++$i >= count($lines) ) break;
				$line = trim($lines[$i]);
			}
			
			$i--;
			$markdownList->AddLast(new MarkdownToken('@ul-plus', MDTKN_UL));
		}
		// (substr($line, 1, 1) !== '-' || substr($line, 1, 2) === '--')
		elseif ( substr($line, 0, 1) === '-' && substr($line, 0, 4) !== '----' && (substr($line, 1, 1) !== '-' || substr($line, 1, 2) === '--') )
		{
			$markdownList->AddLast(new MarkdownToken('', MDTKN_RAW_TEXT));

			///TODO: Jump
			while ( substr($line, 0, 1) === '-' && substr($line, 0, 4) !== '----' && (substr($line, 1, 1) !== '-' || substr($line, 1, 2) === '--') )
			{
				$markdownList->AddLast(new MarkdownToken(MarkdownSimpleText(ltrim(substr($line, 1))), MDTKN_MULTI_TEXT));
				$markdownList->AddLast(new MarkdownToken('@li-minus', MDTKN_LI));

				if ( ++$i >= count($lines) ) break;
				$line = trim($lines[$i]);
			}

			$i--;
			$markdownList->AddLast(new MarkdownToken('@ul-minus', MDTKN_UL));
		}
		elseif ( substr($line, 0, 5) === '&#62;' )
		{
			$markdownList->AddLast(new MarkdownToken('', MDTKN_RAW_TEXT));
			$line = ltrim(substr($line, 5));
			$text = '';
			
			while ( $line !== '' )
			{
				if ( substr($line, 0, 5) === '&#62;' )
					$line = '<br />' . MarkdownSimpleText(ltrim(substr($line, 5)));
				else
					$line = ' ' . MarkdownSimpleText($line);
				
				$markdownList->AddLast(new MarkdownToken($line, MDTKN_MULTI_TEXT));
				
				if ( ++$i >= count($lines) ) break;
				$line = trim($lines[$i]);
			}
			
			$markdownList->AddLast(new MarkdownToken('@blockquote', MDTKN_BLOCKQUOTE));
		}
		elseif ( substr($line, 0, 1) === '#' )
		{
			$headerType = strlen(preg_replace('%\A(#{1,6}).*%', '$1', $line));
			
			$markdownList->AddLast(new MarkdownToken(MarkdownSimpleText(trim(substr($line, $headerType))), MDTKN_RAW_TEXT));
			$markdownList->AddLast(new MarkdownToken($headerType, MDTKN_HEADER));
		}
		elseif ( substr($line, 0, 4) === '====' )
		{
			$markdownList->AddLast(new MarkdownToken(1, MDTKN_HEADER));
		}
		elseif ( substr($line, 0, 4) === '----' )
		{
			$markdownList->AddLast(new MarkdownToken(2, MDTKN_HEADER));
		}
		else
		{
			$olDotFound = strpos($line, '.');
			$olStartNum = substr($line, 0, $olDotFound);
			
			# the !== false part IS needed! Read the php manual about strpos, it's weird
			if ( $olDotFound !== false && is_numeric($olStartNum) )
			{
				$olNextNum = $olStartNum;
				$olLastNum = intval($olNextNum) - 1;
				
				$markdownList->AddLast(new MarkdownToken('', MDTKN_RAW_TEXT));
				
				while ( $olDotFound !== false && is_numeric($olNextNum) && intval($olNextNum) === ($olLastNum + 1) )
				{
					$markdownList->AddLast(new MarkdownToken(MarkdownSimpleText(ltrim(substr($line, $olDotFound + 1))), MDTKN_MULTI_TEXT));
					$markdownList->AddLast(new MarkdownToken('@li-ol', MDTKN_LI));
					
					if ( ++$i >= count($lines) ) break;
					
					$line = trim($lines[$i]);
					$olDotFound = strpos($line, '.');
					$olLastNum = intval($olNextNum);
					$olNextNum = substr($line, 0, (int)$olDotFound);
				}
				
				$i--;
				$markdownList->AddLast(new MarkdownToken(intval($olStartNum), MDTKN_OL));
			}
			else
			{
				$markdownList->AddLast(new MarkdownToken(MarkdownSimpleText($line) . '<br />', MDTKN_RAW_TEXT));
			}
		}
	}

	$output = '';
	$tmpOutput = '';
	$multmpOutput = '';
	$mulOutput = '';
	$tokens = $markdownList->Objects();

	foreach ( $tokens as &$token )
	{
		switch ( $token->type )
		{
			case MDTKN_UL:
				$multmpOutput = '<ul>' . $multmpOutput . $mulOutput . '</ul>';
				break;
			case MDTKN_OL:
				$multmpOutput = '<ol start="' . $token->value . '">' . $multmpOutput . $mulOutput . '</ol>';
				break;
			case MDTKN_LI:
				$mulOutput = '<li>' . $mulOutput . '</li>';
				break;
			case MDTKN_BLOCKQUOTE:
				$multmpOutput = '<blockquote>' . $multmpOutput . $mulOutput . '</blockquote>';
				break;
			case MDTKN_CODEBLOCK:
				$multmpOutput = '<pre>' . $multmpOutput . $mulOutput . '</pre>';
				break;
			case MDTKN_MULTI_TEXT:
				$multmpOutput .= $mulOutput;
				
				$mulOutput = $token->value;
				break;
			case MDTKN_HEADER:
				$tmpOutput = '<h' . $token->value . '>' . $tmpOutput . '</h' . $token->value . '>';
				break;
			case MDTKN_RAW_TEXT:
				$output .= $multmpOutput . $tmpOutput;
				
				$mulOutput = '';
				$multmpOutput = '';
				$tmpOutput = $token->value;
				break;
		}
	}

	$output .= $multmpOutput . $tmpOutput;
	
	return $output;
}

