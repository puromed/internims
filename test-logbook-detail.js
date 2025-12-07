import puppeteer from 'puppeteer';

(async () => {
  const browser = await puppeteer.launch({ 
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const page = await browser.newPage();
  
  try {
    console.log('🚀 Starting logbook detail page test...\n');

    // Step 1: Navigate to login page
    console.log('📍 Step 1: Navigating to login page...');
    await page.goto('https://internims.test/login', { waitUntil: 'networkidle2' });
    console.log('✅ Login page loaded\n');

    // Step 2: Login
    console.log('📍 Step 2: Logging in with fan@internims.test...');
    await page.type('input[type="email"]', 'fan@internims.test');
    await page.type('input[type="password"]', 'Password33');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle2' });
    console.log('✅ Login successful\n');

    // Step 3: Navigate to logbooks page
    console.log('📍 Step 3: Navigating to logbooks page...');
    await page.goto('https://internims.test/logbooks', { waitUntil: 'networkidle2' });
    console.log('✅ Logbooks page loaded\n');

    // Step 4: Check if "View details" link exists
    console.log('📍 Step 4: Checking for "View details" links...');
    const viewDetailsLinks = await page.$$eval(
      'a:has-text("View details")',
      (links) => links.map(l => ({
        text: l.textContent.trim(),
        href: l.href,
        visible: l.offsetParent !== null
      }))
    );

    if (viewDetailsLinks.length > 0) {
      console.log(`✅ Found ${viewDetailsLinks.length} "View details" link(s)\n`);
      viewDetailsLinks.forEach((link, i) => {
        console.log(`   Link ${i + 1}: ${link.text}`);
        console.log(`   URL: ${link.href}`);
        console.log(`   Visible: ${link.visible}\n`);
      });
    } else {
      console.log('❌ No "View details" links found\n');
      process.exit(1);
    }

    // Step 5: Click first "View details" link
    console.log('📍 Step 5: Clicking first "View details" link...');
    const firstLink = await page.$('a:has-text("View details")');
    if (firstLink) {
      await firstLink.click();
      await page.waitForNavigation({ waitUntil: 'networkidle2' });
      console.log('✅ Navigated to detail page\n');
    } else {
      console.log('❌ Could not click link\n');
      process.exit(1);
    }

    // Step 6: Verify detail page elements
    console.log('📍 Step 6: Verifying detail page elements...');
    
    // Check for week number in title
    const weekTitle = await page.$eval('h2', el => el.textContent);
    console.log(`   Title: ${weekTitle}`);
    if (weekTitle.includes('Week')) {
      console.log('   ✅ Week number in title\n');
    }

    // Check for status badge
    const statusBadge = await page.$('span[class*="inline-flex"][class*="rounded-full"]');
    if (statusBadge) {
      const badgeText = await page.evaluate(el => el.textContent, statusBadge);
      console.log(`   Status badge: ${badgeText.trim()}`);
      console.log('   ✅ Status badge visible\n');
    }

    // Check for "Your Entry" card
    const entryCard = await page.$eval(
      'h3',
      (headings) => {
        const heading = Array.from(headings).find(h => h.textContent.includes('Your Entry'));
        return heading ? heading.textContent : null;
      }
    );
    if (entryCard) {
      console.log(`   Entry card: ${entryCard}`);
      console.log('   ✅ Entry card visible\n');
    }

    // Check for AI Insights
    const aiInsights = await page.$eval(
      'h3',
      (headings) => {
        const heading = Array.from(headings).find(h => h.textContent.includes('AI Insights'));
        return heading ? heading.textContent : null;
      }
    );
    if (aiInsights) {
      console.log(`   AI panel: ${aiInsights}`);
      console.log('   ✅ AI Insights panel visible\n');
    }

    // Check for Signed Logsheet
    const logsheetCard = await page.$eval(
      'h3',
      (headings) => {
        const heading = Array.from(headings).find(h => h.textContent.includes('Signed Logsheet'));
        return heading ? heading.textContent : null;
      }
    );
    if (logsheetCard) {
      console.log(`   Logsheet card: ${logsheetCard}`);
      console.log('   ✅ Signed Logsheet card visible\n');
    }

    // Step 7: Check for back link
    console.log('📍 Step 7: Checking for back navigation...');
    const backLink = await page.$('a:has-text("Back to Logbooks")');
    if (backLink) {
      console.log('   ✅ Back to Logbooks link found\n');
    }

    // Step 8: Test back navigation
    console.log('📍 Step 8: Testing back navigation...');
    if (backLink) {
      await backLink.click();
      await page.waitForNavigation({ waitUntil: 'networkidle2' });
      const currentUrl = page.url();
      if (currentUrl.includes('/logbooks') && !currentUrl.includes('/logbooks/')) {
        console.log('   ✅ Back navigation works correctly\n');
      }
    }

    console.log('🎉 All tests passed!\n');

  } catch (error) {
    console.error('❌ Test failed:', error.message);
    console.error(error);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
