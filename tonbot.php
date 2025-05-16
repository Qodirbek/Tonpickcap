<?php
error_reporting(0);

const
title = "tonpick",
versi = "2.0.2",
class_require = "1.1.3",
host = "https://tonpick.game/",
refflink = "https://tonpick.game/?ref=azizbek_advertiser",
youtube = "https://youtu.be/JATnrFZc3ws";

const
turnstile = "0x4AAAAAAA1JQuZADVDIzQ65",
recaptcha = "",
hcaptcha = "";

const
// Bet * MAXWIN_DICE (STOP WIN)
MAXWIN_DICE = 1000;

function DownloadSc($server) {
	$colors = [
		"\033[48;5;16m",  // Black
		"\033[48;5;24m",  // Dark blue
		"\033[48;5;34m",  // Green
		"\033[48;5;44m",  // Blue
		"\033[48;5;54m",  // Light blue
		"\033[48;5;64m",  // Violet
		"\033[48;5;74m",  // Purple
		"\033[48;5;84m",  // Purple-Blue
		"\033[48;5;94m",  // Light purple
		"\033[48;5;104m"  // Pink
	];
	$text = "Proses Download Script...";
	$textLength = strlen($text);

	for ($i = 1; $i <= $textLength; $i++) {
		usleep(150000);  // Delay 150.000 mikrodetik = 0.15 detik
		$percent = round(($i / $textLength) * 100); 
		$bgColor = $colors[$i % count($colors)];
		$coloredText = substr($text, 0, $i);
		$remainingText = substr($text, $i);
		echo $bgColor . $coloredText . "\033[0m" . $remainingText . " {$percent}% \r";
		flush();
	}
	file_put_contents($server."/iewilofficial/class.php",file_get_contents("https://raw.githubusercontent.com/iewilmaestro/myFunctions/refs/heads/main/Class.php"));
	echo "\n\033[48;5;196mProses selesai!,jalankan ulang script\033[0m\n";
	exit;
}

$server = $_SERVER["TMP"];
if(!$server){
	$server = $_SERVER["TMPDIR"];
}

update:
if(!file_exists($server."/iewilofficial/class.php")){
	system("mkdir ".$server."/iewilofficial");
	DownloadSc($server);
}
require $server."/iewilofficial/class.php";

if(class_version < class_require){
	print "\033[1;31mVersi class sudah kadaluarsa\n";
	unlink($server."/iewilofficial/class.php");
	DownloadSc($server);
}

class Bot {
	public $cookie,$uagent;
	public function __construct(){
		Display::Ban(title, versi, 1);
		
		cookie:
		if(empty(Functions::getConfig('cookie'))){
			Display::Cetak("Register",refflink);
			Display::Line();
		}
		$this->cf = new Cloudflare();
		$this->cookie = Functions::setConfig("cookie");
		$this->uagent = Functions::setConfig("user_agent");
		$this->captcha = new Captcha();
		
		if($_SERVER['argv'][1]){
			$cek = json_decode(file_get_contents("https://api-iewil.my.id/getInfo?key=".$_SERVER['argv'][1]),1);
			$this->iewil = ($cek["status"])?new Iewil($_SERVER['argv'][1]):"";
		}
		if($this->iewil){
			Display::Line();
			print Display::Sukses("pertamax status is activated");
			sleep(5);
		}
		$this->scrap = new HtmlScrap();
		Functions::view(youtube);
		
		Display::Ban(title, versi,1);
		$retry = 0;
		dashboard:
		$r = $this->Dashboard();
		if($r['cloudflare']){
			$cloudflare = 1;
			print Display::Error("Cloudflare detect\n");
			Display::Line();
			print Display::Error("Bypass Cloudflare $retry");
			$cf = $this->cf->BypassCf(host);
			$this->cookie = $cf["cookie"];
			$this->uagent = $cf["user-agent"];
			sleep(2);
			print "\r                              \r";
			$retry ++;
			if($retry > 3){
				Functions::removeConfig("cookie");
				Functions::removeConfig("user_agent");
				goto cookie;
			}
			goto dashboard;
		}
		if($cloudflare){
			print Display::Sukses("Cloudflare bypassed");
			Display::Line();
		}
		if(!$r['Login']){
			Functions::removeConfig("cookie");
			Functions::removeConfig("user_agent");
			print Display::Error("Cookie Expired\n");
			Display::Line();
			goto cookie;
		}
		
		$level = $r['Level'];
		Display::Cetak("Username",$r['Username']);
		Display::Cetak("Balance",$r['Balance']);
		Display::Cetak("Wager",$r['Total Wagered']."/".$r['Wagering Target']);
		Display::Cetak("Bal_Api",$this->captcha->getBalance());
		Display::Line();
		
		menu:
		$r = Requests::get(host.'faucet.php',$this->headers());
		$bonus = explode('</span>',explode('<span id="free_spins">',$r[1])[1])[0];
		
		Display::Menu(1, "Claim Bonus [$bonus]");
		Display::Menu(2, "Hourly Bonus [Unlimited]");
		Display::Menu(3, "Dice (Min 0.0001 TRX) [test]");
		print Display::Isi("Nomor");
		$pil = 2; // Avtomatik 2 tanlanadi
		
		Display::Line();
		if($pil == 1)$this->ClaimBonus();
		if($pil == 2){
			if($this->HourlyFaucet()){
				Functions::removeConfig("cookie");
				Functions::removeConfig("user_agent");
				goto cookie;
			}
		}
		if($pil == 3){
			if($this->Dice()){
				Functions::removeConfig("cookie");
				Functions::removeConfig("user_agent");
				goto cookie;
			}
		}
		goto menu;
	}
	
	public function headers(){
		$h[] = "Host: ".parse_url(host)['host'];
		$h[] = "cookie: ".$this->cookie;
		$h[] = "X-Requested-With: XMLHttpRequest";
		$h[] = "user-agent: ".$this->uagent;
		return $h;
	}																			
	
	public function Dashboard(){
		$r = Requests::get(host,$this->headers())[1];
		$data['cloudflare'] = (preg_match('/Just a moment.../', $r))?1:0;
		if(preg_match('/login_button/', $r)){
			$data['Login'] = "";
		}else{
			$data['Login'] = 1;
		}
		preg_match_all('/<b id="(total_wagered|wagering_target)">([^<]+)<\/b>/', $r, $matches);
		$data["Total Wagered"] = $matches[2][0];
		$data["Wagering Target"] = $matches[2][1];
		$data['Level'] = explode('</b>',explode('Your level is  <b>', $r)[1])[0]." ".explode('</div>',explode('aria-valuemax="100">', $r)[1])[0];
		$data['Username'] = trim(explode('&',explode('&username=',$r)[1])[0]);
		$data['Balance'] = explode('<',explode('class="user_balance">',$r)[1])[0];
		return $data;
	}
	private function getCsrf(){
		$cookie = $this->cookie;
		$slice = explode(';', $cookie);
		foreach($slice as $e){
			$data[explode('=', trim($e))[0]] = explode('=', trim($e))[1];
		}
		return $data['csrf_cookie_name'];
	}
	private function rata($str,$x=0,$y=0){
		if($y){
			$len = 6;
		}else{
			$len = 12;
		}
		if(strlen($str)>12){
			$str = substr($str,0,12);
		}
		$lenstr = $len-strlen($str);
		if($x){
			return $str.str_repeat(" ",$lenstr);
		}else{
			return $str.str_repeat(" ",$lenstr).b."|";
		}
	}
	private function roundUpToNextDecimal($number) {
		return ceil($number * 10) / 10;
	}
	private function DiceConfig($config, $dataPost = 0){
		if(!file_exists($config)){
			$data = [];
		}else{
			$data = json_decode(file_get_contents($config),1);
			return $data;
		}
		file_put_contents($config, json_encode($dataPost, JSON_PRETTY_PRINT));
	}
	private function OverDice($base_bet, $multiplier)
	{
		$MAGIC_NUMBER = 9700;
		
		// Rumus
		$MIN_MULTIPLIER = 1.01;
		$MAX_MULTIPLIER = $MAGIC_NUMBER / 2;
		
		if($multiplier < $MIN_MULTIPLIER || $multiplier > $MAX_MULTIPLIER){
			print Display::Error("dahlah\n");
			exit;
		}
		
		$min_win_chance = number_format(($MAGIC_NUMBER / $MAX_MULTIPLIER) / 100, 2, '.', '');
		$max_win_chance = number_format(($MAGIC_NUMBER / $MIN_MULTIPLIER) / 100, 2, '.', '');
		
		$multiplier = number_format(($MAGIC_NUMBER / (10000 - round(10000 - ($MAGIC_NUMBER / $multiplier)))) , 2, '.', '');
		$win_chance = round($MAGIC_NUMBER / $multiplier) / 100;
		$roll_over = 100-$win_chance;
		$profit = sprintf('%.8f',floatval($multiplier*$base_bet-$base_bet));
		$lose = (float)sprintf('%.8f',floatval(($base_bet*$multiplier)/$profit));
		return [
			"base_bet" => sprintf('%.8f',floatval($base_bet)),
			"multiplier" => $multiplier,
			"win_chance" => $win_chance,
			"roll_over" => $roll_over,
			"profit" => $profit,
			// multi selanjutnya jika kalah
			"lose" => $this->roundUpToNextDecimal($lose)
		];
	}
	private function DicePage(){
		$r = Requests::get(host."dice.php", $this->headers())[1];
		$min = trim(explode(';',explode('MIN_BET_AMOUNT = ', $r)[1])[0]);
		$max = trim(explode(';',explode('MAX_BET_AMOUNT = ', $r)[1])[0]);
		return ["min_bet"=>$min,"max_bet"=>$max];
	}
	public function Dice(){
		$h = array_merge($this->headers(), ["x-requested-with: XMLHttpRequest"]);
		$csrf = $this->getCsrf();
		
		$data_awal = $this->Dashboard();
		$balance_awal = $data_awal["Balance"];
		$level_awal = $data_awal["Level"];
		$wager_awal = $data_awal["Total Wagered"];
		$wager_target_awal = $data_awal["Wagering Target"];
		
		$dice_page = $this->DicePage();
		
		$config = "dice.json";
		if(file_exists($config)){
			$dataPost = $this->DiceConfig($config);
			$bet = $dataPost["bet"];
			$payout = $dataPost["multiplier"];
			$stop_loss = $dataPost["stop_loss"];
			print k."---[".p."?".k."] ".p."check config.json";
			sleep(2);
			print "\r                         \r";
			if($bet && $payout && $stop_loss){
				print Display::Sukses("Successfully retrieved the saved config");
				sleep(3);
			}else{
				print Display::Error("Looks like the config is corrupted");
				sleep(3);
				print Display::Sukses("Successfully deleted config");
				sleep(2);
				print Display::Error("Please rerun the script\n");
				exit;
			}
		}else{
			print p."Min Bet ".$dice_page["min_bet"]."\n";
			print "Min Multiplier 1.01\n";
			print "Max Multiplier 4850\n";
			Display::Line();
			
			print Display::isi("Bet");
			$bet = readline();
			
			print n;
			print Display::isi("Multiplier");
			$payout = readline();
			
			$stop_loss = $bet;
			//stop loss = 15x lose
			for($xbet=1; $xbet< 15; $xbet++){
				$stop_loss = $stop_loss*2;
			}
			print p.n."Recomended stop lose $stop_loss (lose^15)\n";
			print Display::isi("Stop Lose");
			$stop_loss = readline();
			$dataPost = ["bet"=>$bet, "multiplier"=>$payout, "stop_loss"=>$stop_loss];
			$this->DiceConfig($config, $dataPost);
			print Display::Sukses("Save config to dice.json");
			sleep(3);
		}
		if($bet > $balance_awal){
			exit(Display::Error("insufficient balance\n"));
		}
		if($bet < $dice_page["min_bet"] || $bet > $dice_page["max_bet"]){
			exit(Display::Error("bet must be greater than min bet and less than max bet\n"));
		}
		if($payout < 1.01 || $payout > 4850){
			exit(Display::Error("Multiplier must be greater than min Multiplier and less than max Multiplier\n"));
		}
		
		$bet_on = "higher";
		
		$bet_awal = $bet;
		$maxwin = $bet_awal*MAXWIN_DICE;
		
		$cuan = 0;
		$roll = 0;
		$total_win = 0;
		$total_lose = 0;
		
		Display::Ban(title, versi);
		print Display::Title("Dice Information");
		$OverDice = $this->OverDice($bet, $payout);
		
		print "[~]Bet		: {$OverDice['base_bet']}\n";
		print "[~]Multiplier	: {$OverDice['multiplier']}\n";
		print "[~]Win Chance	: ".(100-$OverDice['roll_over'])."\n";
		print "[~]Roll Over	: {$OverDice['roll_over']}\n";
		print "[~]Profit	: {$OverDice['profit']}\n";
		print "[~]Lose Multi	: {$OverDice['lose']}\n";
		print "[~]Stop profit	: ".sprintf('%.8f',floatval($maxwin))."\n";
		print "[~]Stop lose	: ".sprintf('%.8f',floatval($stop_loss))."\n";
		Display::Line();
		print Display::Error("Press Enter to Continue!\n");
		Display::Line();
		readline();
		print Display::Title("Dice Progress");
		print p.$this->rata("Balance").p.$this->rata("Num",'',1).p.$this->rata("Bet Amount").p.$this->rata("W / L").p.$this->rata("Profit",1).n;
		Display::Line();
		while(true){
			$data = "action=bet_game_dice&bet_amount=$bet&payout=$payout&bet_on=$bet_on&csrf_test_name=$csrf";
			$r = json_decode(Requests::post(host.'process.php',$h, $data)[1],1);
			if(!$r['balance']){
				if($r['mes'] == "Insufficient balance!"){
					Display::Line();
					print Display::Error("INSUFFICIENT!!\n");
					print p."[~]Lose		: {$cuan}\n";
					print "[~]Roll		: {$roll}\n";
					print "[~]Win Roll	: {$total_win}\n";
					print "[~]Lose Roll	: {$total_lose}\n";
					Display::Line();
					break;
				}
				return 1;
			}
			$balance_dice_awal = sprintf('%.8f',floatval($r['balance']/100000000));
			if($r){
				$profit = ($r['amount'] < 0)? 0:1;
				if($profit){
					$profit_ammount = strip_tags($r['bet_data']['profit']);
					$cuan = sprintf('%.8f',floatval($cuan+$profit_ammount));
					$bet = $bet_awal;
					$war = h;
					$tam = "+";
					$total_win++;
				}else{
					$cuan = sprintf('%.8f',floatval($cuan-$bet));
					$bet = $bet*$OverDice['lose'];
					$war = m;
					$tam = "";
					$total_lose++;
				}
				print k.$this->rata(sprintf('%.8f',floatval($r['balance']/100000000)));
				print p.$this->rata($r['num'],'',1);
				print p.$this->rata($r['bet_data']['bet_amount']);
				print $war.$this->rata($tam.strip_tags($r['bet_data']['profit']));
				print p.$this->rata($cuan,1);
				$total_win_lose = ($profit)? h."($total_win)":m."($total_lose)";
				print $total_win_lose.n;
			}
			if(sprintf('%.8f',floatval($bet)) > $balance_dice_awal){
				Display::Line();
				print Display::Error("BANKRUPT!!\n");
				print p."[~]Lose		: {$cuan}\n";
				print "[~]Roll		: {$roll}\n";
				print "[~]Win Roll	: {$total_win}\n";
				print "[~]Lose Roll	: {$total_lose}\n";
				Display::Line();
				break;
			}
			$roll ++;
			if($cuan >= $maxwin){
				Display::Line();
				print p."[~]Profit	: {$cuan}\n";
				print "[~]Roll		: {$roll}\n";
				print "[~]Win Roll	: {$total_win}\n";
				print "[~]Lose Roll	: {$total_lose}\n";
				Display::Line();
				break;
			}
			if($bet >= $stop_loss){
				Display::Line();
				print Display::Error("STOP LOSE!!");
				print p."[~]Lose		: {$cuan}\n";
				print "[~]Roll		: {$roll}\n";
				print "[~]Win Roll	: {$total_win}\n";
				print "[~]Lose Roll	: {$total_lose}\n";
				Display::Line();
				break;
			}
			//sleep(1);
		}
		$data_akhir = $this->Dashboard();
		$balance_akhir = $data_akhir["Balance"];
		$level_akhir = $data_akhir["Level"];
		$wager_akhir = $data_akhir["Total Wagered"];
		$wager_target_akhir = $data_akhir["Wagering Target"];
		
		print p.$this->rata("Data name").p.$this->rata("Init data").p.$this->rata("Final data").p.$this->rata("Compare",1).n;
		Display::Line();
		print p.$this->rata("Balance");
		print p.$this->rata(sprintf('%.8f',floatval($balance_awal)));
		print p.$this->rata(sprintf('%.8f',floatval($balance_akhir)));
		$compare_balance = $balance_akhir-$balance_awal;
		if($compare_balance < 0){
			print m.$this->rata(sprintf('%.8f',floatval($compare_balance)),1)."\n";
		}else{
			print h.$this->rata(sprintf('%.8f',floatval($compare_balance)),1)."\n";
		}
		print p.$this->rata("Wager");
		print p.$this->rata(sprintf('%.8f',floatval($wager_awal)));
		print p.$this->rata(sprintf('%.8f',floatval($wager_akhir)));
		$compare_wager = $wager_akhir-$wager_awal;
		print h.$this->rata(sprintf('%.8f',floatval($compare_wager)),1)."\n";
		Display::Line();
		return;
	}
	public function HourlyFaucet(){
		$retry = 0;
		while(true){
			$r = Requests::get(host.'faucet.php',$this->headers());
			$cek = $this->scrap->Result($r[1]);
			if($cek['cloudflare']){
				$cloudflare = 1;
				print Display::Error("Cloudflare Detect\n");
				Display::Line();
				print Display::Error("Bypass Cloudflare $retry");
				$cf = $this->cf->BypassCf(host);
				$this->cookie = $cf["cookie"];
				$this->uagent = $cf["user-agent"];
				sleep(2);
				print "\r                              \r";
				$retry ++;
				if($retry > 3){
					return 1;
				}
				continue;
			}
			if($cloudflare){
				print Display::Sukses("Cloudflare bypassed");
				Display::Line();
				$cloudflare = false;
			}
			$retry = 0;
			$tmr = explode('|',explode('select_hourly_faucet|',$r[1])[1])[0];
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $r[0], $matches);
			$cookies = array();
			foreach($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
			}
			
			$recaptcha = recaptcha;
			$turnstile = turnstile;
			$hcaptcha = hcaptcha;
			
			if($hcaptcha && preg_match('/'.explode('-',$hcaptcha)[0].'/', $r[1])){
				$cap = $this->captcha->Hcaptcha($hcaptcha, host.'faucet.php');
				if(!$cap)continue;
				$data = 'action=claim_hourly_faucet&g-recaptcha-response=null&h-captcha-response='.$cap.'&captcha=&ft=&csrf_test_name='.$cookies['csrf_cookie_name'];
			}elseif($recaptcha && preg_match('/'.$recaptcha.'/', $r[1])){
				$cap = $this->captcha->RecaptchaV2($recaptcha, host.'faucet.php');
				if(!$cap)continue;
				$data = 'action=claim_hourly_faucet&g-recaptcha-response='.$cap.'&h-captcha-response=null&captcha=&ft=&csrf_test_name='.$cookies['csrf_cookie_name'];
			}elseif($turnstile && preg_match('/'.$turnstile.'/', $r[1])){
				$cap = ($this->iewil)? $this->iewil->Turnstile($turnstile, host.'faucet.php'):$this->captcha->Turnstile($turnstile, host.'faucet.php');
				if(!$cap)continue;
				$data = 'action=claim_hourly_faucet&clbt=1&g-recaptcha-response=null&captcha=&h-captcha-response=null&c_captcha_response='.$cap.'&csrf_test_name='.$cookies['csrf_cookie_name'];
			}else{
				print Display::Error("Sitekey Error\n");
				continue;
			}
			
			$r = json_decode(Requests::post(host.'process.php',$this->headers(),$data)[1],1);
			if($r["ret"]){
				Display::Cetak("Number",$r["num"]);
				print Display::Sukses($r["mes"]);
				Display::Cetak("Balance",$this->Dashboard()["Balance"]);
				Display::Cetak("Bal_Api",$this->captcha->getBalance());
				Display::Line();
			}else{
				if($r['mes']){
					print Display::Error($r['mes']."\n");
				}else{
					print_r($r);
				}
				Display::Line();
			}
			Functions::Tmr(3600);
		}
	}
	
	public function ClaimBonus(){
		while(true){
			$r = Requests::get(host.'faucet.php',$this->headers());
			$bonus = explode('</span>',explode('<span id="free_spins">',$r[1])[1])[0];
			if(!$bonus){
				print Display::Error("No Bonus\n");
				break;
			}
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $r[0], $matches);
			$cookies = array();
			foreach($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
			}
			$data = "action=claim_bonus_faucet&csrf_test_name=".$cookies['csrf_cookie_name'];
			$r = json_decode(Requests::post(host.'process.php',$this->headers(),$data)[1],1);
			if($r["ret"]){
				Display::Cetak("Number",$r["num"]);
				print Display::Sukses($r["mes"]);
				Display::Cetak("Balance",$this->Dashboard()["Balance"]);
				Display::Line();
			}
		}
	}
}
new Bot();