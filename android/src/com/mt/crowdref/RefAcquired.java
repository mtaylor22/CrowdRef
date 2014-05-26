package com.mt.crowdref;

import com.loopj.android.http.AsyncHttpResponseHandler;
import com.loopj.android.http.RequestParams;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

public class RefAcquired extends Activity {

	/** Called when the activity is first created. */
	@Override
	public void onCreate(Bundle savedInstanceState) {
	    super.onCreate(savedInstanceState);

		setContentView(R.layout.activity_main);
	    Intent intent = getIntent();
		if (savedInstanceState == null && intent != null) {
		    Log.d("tag", "intent != null");
		    if (intent.getAction() == (Intent.ACTION_SEND)) {
		        Log.d("tag", "intent.getAction().equals(Intent.ACTION_SEND)");
		        String message = intent.getStringExtra(Intent.EXTRA_TEXT);
		        Toast.makeText(getApplicationContext(), message, Toast.LENGTH_LONG).show();
		        EditText et = (EditText) findViewById(R.id.editText1);
		        et.setText(message);
		    }
		}

		final Button button = (Button) findViewById(R.id.button1);
        button.setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) {
                // Perform action on click
//            	Intent i = new Intent(MainActivity.this, RefAcquired.class);
//            	startActivity(i);
        		RequestParams params = new RequestParams();

        	    EditText refname = (EditText)findViewById(R.id.editText1);
        	    
        		params.put("ref_text", refname.getText().toString());
        		params.put("login_submit", "SET");
	    		
        		GlobalData.client.post("http://crowdref.atwebpages.com/mobile_submitref.php", params, new AsyncHttpResponseHandler() {
            	    @Override
            	    public void onSuccess(String response) {
            	    	if (response.equals("{\"status\":0}")){
            	    		Toast.makeText(RefAcquired.this, "Url Submitted!", Toast.LENGTH_LONG).show();
            	    	}else if(response.equals("{\"status\":1}")){
            	    		Toast.makeText(RefAcquired.this, "Url malformed, or something idk!", Toast.LENGTH_LONG).show();
            	    	}else{
            	    		Toast.makeText(RefAcquired.this, "Error: " + response, Toast.LENGTH_LONG).show();            	    		
            	    	}
            	    }
            	});
            }
        });
	}

}
