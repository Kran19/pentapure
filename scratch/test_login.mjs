async function testLogin(username, password) {
  const res = await fetch('https://pentapureadmin.in/public/login');
  const html = await res.text();
  const cookies = res.headers.getSetCookie ? res.headers.getSetCookie() : [res.headers.get('set-cookie')];
  const tokenMatch = html.match(/name="_token" value="([^"]+)"/);
  const token = tokenMatch ? tokenMatch[1] : null;

  const cookieHeader = cookies.map(c => c.split(';')[0]).join('; ');
  const body = new URLSearchParams({
    _token: token,
    username: username,
    password: password
  });

  const loginRes = await fetch('https://pentapureadmin.in/public/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Cookie': cookieHeader
    },
    body: body.toString(),
    redirect: 'manual'
  });

  const loginCookies = loginRes.headers.getSetCookie ? loginRes.headers.getSetCookie() : [loginRes.headers.get('set-cookie')];
  const finalCookieHeader = loginCookies.map(c => c.split(';')[0]).join('; ');

  const loc = loginRes.headers.get('location');
  const afterRes = await fetch(loc || 'https://pentapureadmin.in/public/login', {
    headers: {
      'Cookie': finalCookieHeader
    }
  });

  const afterHtml = await afterRes.text();
  // check for errors or notifications
  const matchAlert = afterHtml.match(/Swal\.fire\([^)]+\)/) || afterHtml.match(/class="[^"]*alert[^"]*"[^>]*>([^<]+)/) || afterHtml.match(/session\('error'\)/);
  console.log(`[${username} : ${password}] -> Status: ${loginRes.status}, Loc: ${loc}, Alert:`, matchAlert ? matchAlert[0] : 'None found');
  if (loc && !loc.includes('login')) {
    console.log(`SUCCESS for ${username} -> Redirected to: ${loc}`);
  }
}

async function run() {
  await testLogin('admin', 'admin@123');
  await testLogin('ADMIN', 'admin@123');
  await testLogin('sales', 'sales@123');
  await testLogin('dispatch', 'dispatch@123');
  await testLogin('cashier', 'cashier@123');
  await testLogin('attendance', 'attendance@123');
}

run();
