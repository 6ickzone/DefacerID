<?php
/*
 * ~ bulk submissions to the Defacer API ~
 * original CLI: github.com/defacerID/
 * webbased    : 0x6ick
 * contact     : t.me/yungx6ick
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['action']) || $input['action'] !== 'check') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'invalid action']);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');

    $notifier = $input['notifier'] ?? 'Attacker';
    $team     = $input['team'] ?? 'Team';
    $mirror   = $input['mirror'] ?? 'defacerid';
    $url      = trim($input['url'] ?? '');

    if ($url === '') {
        echo json_encode(['ok' => false, 'msg' => 'empty url']);
        exit;
    }

    // --- API DefacerID ---
    $payload = json_encode([
        "notifier" => $notifier,
        "team"     => $team,
        "url"      => $url,
        "poc"      => "Not available",
        "reason"   => "Not available"
    ]);

    $ch = curl_init("https://api.defacer.id/notify");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $resp = curl_exec($ch);
    $curl_err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = 'ERROR';
    $note = 'Bekas/BanList';
    $message = '';

    if ($resp !== false && $http_code >= 200 && $http_code < 300) {
        $json = json_decode($resp, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $message = $json['message'] ?? '';
            if (stripos($message, 'success') !== false || stripos($message, 'ok') !== false || stripos($message, 'created') !== false) {
                $status = 'SUCCESS';
                $note = 'Perawan';
            }
        } else {
            $status = 'SUCCESS';
            $note = 'Perawan';
            $message = substr($resp, 0, 200);
        }
    } else {
        $message = $curl_err ?: "HTTP:$http_code " . substr($resp, 0, 200);
    }

    echo json_encode([
        'ok' => true,
        'url' => $url,
        'status' => $status,
        'note' => $note,
        'http' => $http_code,
        'message' => $message
    ]);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>DefacerID Notifier</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* --- Trash Polka Inspired--- */
:root {
  --bg-black: #0a0a0a;    
  --text-white: #ffffff; 
  --text-muted: #aaaaaa;  
  --accent-red: #ff0000;  
}

body {
  background: var(--bg-black);
  color: var(--text-white); 
  font-family: 'Courier New', Courier, monospace;
  padding: 18px;
}

.formbox {
  background: transparent; 
  padding: 12px;
  border-radius: 0; 
  display: inline-block;
  border: 1px solid var(--accent-red);
  box-shadow: 0 0 15px rgba(255, 0, 0, 0.4);
}


.put,
select,
input[type=text] {
  width: 100%;
  box-sizing: border-box;
  background: transparent;
  color: var(--text-white);
  border: none;
  border-bottom: 2px solid var(--text-white); 
  padding: 8px 4px;
  border-radius: 0;
  font-family: inherit;
}


.put:focus,
select:focus,
input[type=text]:focus {
  outline: none;
  border-bottom-color: var(--accent-red);
}

.btn {
  padding: 8px 12px;
  margin-top: 8px;
  cursor: pointer;
  background: var(--accent-red);
  color: var(--text-white);
  border: 1px solid var(--accent-red);
  font-weight: bold;
  text-transform: uppercase;
  transition: all 0.2s ease;
}

.btn:hover {
  background: var(--text-white);
  color: var(--accent-red);
  border: 1px solid var(--accent-red);
}

.table-wrap {
  margin-top: 14px;
  width: 100%;
  max-width: 720px;
}

.output-table {
  width: 100%;
  border-collapse: collapse;
  background: #111;
}

.output-table td {
  padding: 6px 8px;
  border-bottom: 1px dashed var(--accent-red); 
}

.output-table tr:last-child td {
  border-bottom: none;
}

.small {
  font-size: 12px;
  color: var(--text-muted);
}

label {
  display: block;
  margin-top: 8px;
  color: var(--text-muted);
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 1px;
}
</style>
</head>
<body>
<div class="formbox">
  <div><span style="color:red">Defacer</span><span style="color:#fff">ID</span> <span class="small">Notifier</span></div>
  <form id="theform" onsubmit="return handleSubmit(event);">
    <label>Automate bulk submissions to the Defacer API</label>
    <input type="text" id="notifier" placeholder="Attacker" value="Attacker">
    <input type="hidden" id="mirror" value="defacerid">
    <label>Team</label>
    <input type="text" id="team" placeholder="Team" value="Team">
    <label style="margin-top:10px">Domains / URLs (one per line)</label>
    <textarea id="domains" class="put" style="width:250px;height:120px;margin-top:8px;" placeholder="http://"></textarea>
    <div>
      <input class="btn" type="submit" value="Submit.." id="submitBtn">
      <button type="button" onclick="clearOutput()" class="btn">Clear</button>
      <span class="small">processed one-by-one</span>
    </div>
  </form>
</div>

<table class="output-table" id="outtable"></table>
<br><hr>
<footer>
  <small>Converted from CLI by 0x6ick</a>. </small>
</footer>
<script>
function escapeHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function appendRow(html){document.getElementById('outtable').insertAdjacentHTML('beforeend',html);window.scrollTo(0,document.body.scrollHeight);}
function clearOutput(){document.getElementById('outtable').innerHTML='';}
async function handleSubmit(e){
  e.preventDefault();
  const notifier=document.getElementById('notifier').value||'Attacker';
  const team=document.getElementById('team').value||'Team';
  const mirror=document.getElementById('mirror').value||'defacerid';
  const lines=(document.getElementById('domains').value||'').split(/\r?\n/).map(l=>l.trim()).filter(l=>l!=='');
  if(lines.length===0){alert('Masukin dulu minimal 1 URL/domain, bro.');return false;}
  const btn=document.getElementById('submitBtn');btn.disabled=true;btn.value='Processing...';
  for(const url of lines){
    appendRow(`<tr><td><font color='red'>Defacer</font><font color='#fff'>ID</font> =&gt; <font color='gold'>${escapeHtml(url)}</font> : <span style='color:#aaa'>PROCESSING...</span></td></tr>`);
    try{
      const resp=await fetch(location.href,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'check',notifier,team,mirror,url})});
      const data=await resp.json();
      appendRow(`<tr><td><font color='red'>Defacer</font><font color='#fff'>ID</font> =&gt; <font color='gold'>${escapeHtml(url)}</font> : Status =&gt; <span style='color:${data.status==='SUCCESS'?'green':'red'}'>${escapeHtml(data.status)}</span> [${escapeHtml(data.note)}]</td></tr>`);
    }catch(err){
      appendRow(`<tr><td><font color='red'>Defacer</font><font color='#fff'>ID</font> =&gt; <font color='gold'>${escapeHtml(url)}</font> : <span style='color:red'>ERROR</span> [JS/Network]</td></tr>`);
    }
  }
  btn.disabled=false;btn.value='Submit..';return false;
}
</script>
</body>
</html>
