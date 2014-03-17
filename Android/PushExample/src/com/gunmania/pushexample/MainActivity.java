package com.gunmania.pushexample;

import java.util.ArrayList;

import uk.co.senab.actionbarpulltorefresh.extras.actionbarcompat.PullToRefreshLayout;
import uk.co.senab.actionbarpulltorefresh.library.ActionBarPullToRefresh;
import uk.co.senab.actionbarpulltorefresh.library.listeners.OnRefreshListener;

import com.parse.ParseInstallation;
import com.parse.SaveCallback;

import android.net.Uri;
import android.net.http.SslError;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.res.Configuration;
import android.graphics.Bitmap;
import android.support.v7.app.ActionBar;
import android.support.v7.app.ActionBarActivity;
import android.view.KeyEvent;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.CookieSyncManager;
import android.webkit.SslErrorHandler;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;

public class MainActivity extends ActionBarActivity implements OnRefreshListener {
    private myWebChromeClient mWebChromeClient;
	
	Menu mMenu;
	public static String objectId;
	
	private PullToRefreshLayout mPullToRefreshLayout;
	static WebView SiteView;
	ProgressBar mProgressHorizontal;
	public static ArrayList<Activity> at = new ArrayList<Activity>();
	
    private ValueCallback<Uri> mUploadMessage;
    private final static int FILECHOOSER_RESULTCODE = 1;
    
    @Override
    protected void onActivityResult(int requestCode, int resultCode,
    		Intent intent) {
    	if (requestCode == FILECHOOSER_RESULTCODE) {
    		if (null == mUploadMessage)
    			return;
    		Uri result = intent == null || resultCode != RESULT_OK ? null
    				: intent.getData();
    		mUploadMessage.onReceiveValue(result);
    		mUploadMessage = null;
    	}
    }
    
	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
        at.add(this);
        setContentView(R.layout.activity_main);
        
        mProgressHorizontal = (ProgressBar)findViewById(R.id.progress_horizontal);
                
        CookieSyncManager.createInstance(this);
        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);
        cookieManager.setCookie("http://(도메인)", "app=1");
        
        SiteView = (WebView)findViewById(R.id.webView1);
        SiteView.getSettings().setJavaScriptEnabled(true);
        //SiteView.getSettings().setPluginState(PluginState. ON);
        mWebChromeClient = new myWebChromeClient();
        SiteView.setWebChromeClient(mWebChromeClient);
        SiteView.getSettings().setSaveFormData(true);
        SiteView.getSettings().setSavePassword(true);
        SiteView.getSettings().setSupportMultipleWindows(true);
        SiteView.getSettings().setAppCacheEnabled(true);
        SiteView.getSettings().setDatabaseEnabled(true);
        SiteView.getSettings().setDomStorageEnabled(true);
        SiteView.getSettings().setLightTouchEnabled(true);
        SiteView.setVerticalScrollbarOverlay(true);
        SiteView.setWebViewClient(new MyView());
        
        mPullToRefreshLayout = (PullToRefreshLayout) findViewById(R.id.ptr_webview);
        ActionBarPullToRefresh.from(this)
        .allChildrenArePullable()
        .listener(this)
        .setup(mPullToRefreshLayout);
        
        SharedPreferences pref = PreferenceManager.getDefaultSharedPreferences(this);
    	MainApplication.is_push =  pref.getBoolean("pushnotify", true);

		if(MainApplication.is_push == true) {
			if (ParseInstallation.getCurrentInstallation().getObjectId() == null) {
	    		ParseInstallation.getCurrentInstallation().saveInBackground(new SaveCallback() {
	    			@Override
	    			public void done(com.parse.ParseException arg0) {
	    				objectId = ParseInstallation.getCurrentInstallation().getObjectId();
	    				SiteView.loadUrl("http://(도메인)/?m=1&obid=" + objectId);
	    			}
	    		});
	    	}
	    	else {
	    		objectId = ParseInstallation.getCurrentInstallation().getObjectId();
	    		SiteView.loadUrl("http://(도메인)/?m=1&obid=" + objectId);
	    	}
	    }
	    else {
	    	ParseInstallation.getCurrentInstallation().deleteInBackground();
	    	SiteView.loadUrl("http://(도메인)/?m=1");
	    }
    	CookieSyncManager.getInstance().startSync();
       
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		mMenu = menu;
		MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.main, menu);
        if (MainApplication.is_push == true) {
        	menu.findItem(R.id.action_share).setVisible(false);
        }
        return super.onCreateOptionsMenu(menu);
	}
	
	@Override
    public boolean onOptionsItemSelected(MenuItem item) {
    	switch(item.getItemId()){
    	case R.id.action_exit:
    		//finish();
    		for (int i = 0; i < MainActivity.at.size(); i++) {
    			   MainActivity.at.get(i).finish();
    		}
    		break;
    	case R.id.action_share:
    		Intent intentSend  = new Intent(Intent.ACTION_SEND);
    		intentSend.setType("text/plain");
    		String PageUrl = SiteView.getUrl();
    		String PageTitle = SiteView.getTitle();
    		intentSend.putExtra(Intent.EXTRA_TEXT, PageTitle + " " + PageUrl);
    		startActivity(Intent.createChooser(intentSend, "공유"));
    		break;
    	case R.id.action_setting:
    		Intent i = new Intent(this, SettingActivity.class);
    	    startActivity(i);
    	    break;
    	default:
    		break;
        }
    	return false;
    }
    
    class MyView extends WebViewClient 
    {
    	@Override
        public boolean shouldOverrideUrlLoading(WebView view, String url)
        {
        	if (!url.startsWith("http://(도메인)") && !url.startsWith("https://(도메인)")) 
            {
                Intent i = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                startActivity(i);
                return true;
            }
            else {
            	view.loadUrl(url);
            	return true;
            }
        }
        
        @Override
        public void onPageStarted(WebView view , String url, Bitmap favicon) {
        	super.onPageStarted(view , url, favicon);
        	mProgressHorizontal.setVisibility(View.VISIBLE);
        	view.setClickable(false);
        	
        }

        @Override
        public void onPageFinished(WebView view, String url) {
            super.onPageFinished(view, url);
            CookieSyncManager.getInstance().sync();
            if (mPullToRefreshLayout.isRefreshing()) {
                mPullToRefreshLayout.setRefreshComplete();
            }
            mProgressHorizontal.setVisibility(View.INVISIBLE);
            if(MainApplication.is_push == true ) {
            	mMenu.findItem(R.id.action_share).setVisible(true);
            }
            view.setClickable(true);

        }
        
    	@Override  
        public void onReceivedSslError(WebView view, SslErrorHandler handler, SslError error) {  
            handler.proceed();
        }   
    }
    
    class myWebChromeClient extends WebChromeClient
    {
        ActionBar actionBar = getSupportActionBar();
    	
    	public void openFileChooser(ValueCallback<Uri> uploadMsg, String acceptType, String capture) {
    		mUploadMessage = uploadMsg;
    		Intent i = new Intent(Intent.ACTION_GET_CONTENT);
    		i.addCategory(Intent.CATEGORY_OPENABLE);
    		i.setType("*/*");
    		MainActivity.this.startActivityForResult(
    				Intent.createChooser(i, "사진을 선택하세요"),
    				FILECHOOSER_RESULTCODE);
    	}

    	public void openFileChooser(ValueCallback<Uri> uploadMsg, String acceptType) {
			openFileChooser(uploadMsg,"","");
		}

		public void openFileChooser(ValueCallback<Uri> uploadMsg) {
			openFileChooser(uploadMsg,"", "");
		}


    	@Override
    	public void onProgressChanged(WebView view, int newProgress) {
    		mProgressHorizontal.setProgress(newProgress);
    	}
     }     
    
    public boolean onKeyDown(int keyCode, KeyEvent event)
    {
    	if(keyCode==KeyEvent.KEYCODE_BACK)
    	{
    		if(SiteView.canGoBack())
    		{
    			SiteView.goBack();
    			return true;
    		}
    	}
    	return super.onKeyDown(keyCode, event);
    }
	
    
    @Override
    public void onConfigurationChanged(Configuration newConfig) {
       super.onConfigurationChanged(newConfig);
    }
        
    private void clearApplicationCache(java.io.File dir){
        if(dir==null)
            dir = getCacheDir();
        else;
        if(dir==null)
            return;
        else;
        java.io.File[] children = dir.listFiles();
        try{
            for(int i=0;i<children.length;i++)
                if(children[i].isDirectory())
                    clearApplicationCache(children[i]);
                else children[i].delete();
        }
        catch(Exception e){}
    }

	@Override
    public void onRefreshStarted(View view) {
        SiteView.reload();
    }
	
	@Override
    public void onDestroy() {
    	super.onDestroy();
    	clearApplicationCache(null);
    }
    
    @Override
    protected void onResume() {
    	super.onResume();
    }
    
    @Override
    protected void onPause() {
    	super.onPause();
    	CookieSyncManager.getInstance().stopSync();
    	
    	SharedPreferences pref = PreferenceManager.getDefaultSharedPreferences(this);
    	MainApplication.is_push =  pref.getBoolean("pushnotify", true);
    }
    
    @Override
    public void onStop() {
      super.onStop();
    }
}
