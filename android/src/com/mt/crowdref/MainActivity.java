package com.mt.crowdref;

import java.io.BufferedInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.URL;
import java.net.URLConnection;

import org.apache.http.HttpResponse;
import org.apache.http.HttpStatus;
import org.apache.http.StatusLine;
import org.apache.http.client.CookieStore;
import org.apache.http.client.HttpClient;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.util.ByteArrayBuffer;

import android.support.v7.app.ActionBarActivity;
import android.support.v4.app.Fragment;
import android.content.Intent;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import com.loopj.android.http.*;


public class MainActivity extends ActionBarActivity {

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.fragment_main);
		PersistentCookieStore cs = new PersistentCookieStore(this);
		GlobalData.client.setCookieStore(cs);
        
		GlobalData.client.get("http://crowdref.atwebpages.com/mobile_login.php", new AsyncHttpResponseHandler() {
    	    @Override
    	    public void onSuccess(String response) {
    	    	if (response.equals("{\"status\":0}")){
    	    		Toast.makeText(MainActivity.this, "Not Logged in", Toast.LENGTH_LONG).show();
                	Intent i = new Intent(MainActivity.this, Login.class);
                	startActivity(i);
//                	http://stackoverflow.com/questions/3587254/how-do-i-manage-cookies-with-httpclient-in-android-and-or-java
    	    	}else if (response.equals("{\"status\":1}")){
    	    		Toast.makeText(MainActivity.this, "Logged in", Toast.LENGTH_LONG).show();
    	    		final Button button = (Button) findViewById(R.id.button1);
    	            button.setOnClickListener(new View.OnClickListener() {
    	                public void onClick(View v) {
    	                    // Perform action on click
//    	                	Intent i = new Intent(MainActivity.this, RefAcquired.class);
//    	                	startActivity(i);
    	            		RequestParams params = new RequestParams();

    	            	    EditText refname = (EditText)findViewById(R.id.editText1);
    	            	    
    	            		params.put("ref_text", refname.getText().toString());
    	            		params.put("login_submit", "SET");
    	    	    		
    	            		GlobalData.client.post("http://crowdref.atwebpages.com/mobile_submitref.php", params, new AsyncHttpResponseHandler() {
    	                	    @Override
    	                	    public void onSuccess(String response) {
    	                	    	if (response.equals("{\"status\":0}")){
    	                	    		Toast.makeText(MainActivity.this, "Url Submitted!", Toast.LENGTH_LONG).show();
    	                	    	}else if(response.equals("{\"status\":1}")){
    	                	    		Toast.makeText(MainActivity.this, "Url malformed, or something idk!", Toast.LENGTH_LONG).show();
    	                	    	}else{
    	                	    		Toast.makeText(MainActivity.this, "Error: " + response, Toast.LENGTH_LONG).show();            	    		
    	                	    	}
    	                	    }
    	                	});
    	                }
    	            });
    	    	}else {
    	    		Toast.makeText(MainActivity.this, "Error", Toast.LENGTH_LONG).show();
    	    	}
    	    }
    	});
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {

		// Inflate the menu; this adds items to the action bar if it is present.
		getMenuInflater().inflate(R.menu.main, menu);
		return true;
	}

	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
		// Handle action bar item clicks here. The action bar will
		// automatically handle clicks on the Home/Up button, so long
		// as you specify a parent activity in AndroidManifest.xml.
		int id = item.getItemId();
		if (id == R.id.action_settings) {
			return true;
		}
		return super.onOptionsItemSelected(item);
	}

	/**
	 * A placeholder fragment containing a simple view.
	 */
	public static class PlaceholderFragment extends Fragment {

		public PlaceholderFragment() {
		}

		@Override
		public View onCreateView(LayoutInflater inflater, ViewGroup container,
				Bundle savedInstanceState) {
			View rootView = inflater.inflate(R.layout.fragment_main, container,
					false);
			return rootView;
		}
	}
}