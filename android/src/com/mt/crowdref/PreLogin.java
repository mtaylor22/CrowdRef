package com.mt.crowdref;

import com.loopj.android.http.AsyncHttpClient;
import com.loopj.android.http.AsyncHttpResponseHandler;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.widget.Toast;

public class PreLogin extends Activity {

	/** Called when the activity is first created. */
	@Override
	public void onCreate(Bundle savedInstanceState) {
	    super.onCreate(savedInstanceState);

		setContentView(R.layout.prelogin_main);
		AsyncHttpClient client = new AsyncHttpClient();
    	client.get("http://crowdref.atwebpages.com/mobile_login.php", new AsyncHttpResponseHandler() {
    	    @Override
    	    public void onSuccess(String response) {
    	    	if (response.equals("{\"status\":0}")){
    	    		Toast.makeText(PreLogin.this, "Not Logged in", Toast.LENGTH_LONG).show();
                	Intent i = new Intent(PreLogin.this, Login.class);
                	startActivity(i);
    	    	}else{
    	    		Toast.makeText(PreLogin.this, "Logged in, or ?", Toast.LENGTH_LONG).show();
    	    	}
    	    }
    	});
	}

}
