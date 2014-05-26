package com.mt.crowdref;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import com.loopj.android.http.*;

public class Login extends Activity {

	/** Called when the activity is first created. */
	@Override
	public void onCreate(Bundle savedInstanceState) {
	    super.onCreate(savedInstanceState);

		setContentView(R.layout.login_main);
		
		
		final Button button = (Button) findViewById(R.id.button1);
        button.setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) {
                // Perform action on click
//            	Intent i = new Intent(MainActivity.this, RefAcquired.class);
//            	startActivity(i);
        		RequestParams params = new RequestParams();

        	    EditText uname = (EditText)findViewById(R.id.editText1);
        	    EditText pass = (EditText)findViewById(R.id.editText2);
        	    
        		params.put("login_email", uname.getText().toString());
        		params.put("login_password", pass.getText().toString());
        		params.put("login_submit", "SET");
	    		
        		GlobalData.client.post("http://crowdref.atwebpages.com/mobile_login.php", params, new AsyncHttpResponseHandler() {
            	    @Override
            	    public void onSuccess(String response) {
            	    	if (response.equals("{\"status\":0}")){
            	    		Toast.makeText(Login.this, "Incorrect, or not valid!", Toast.LENGTH_LONG).show();
            	    	}else if(response.equals("{\"status\":1}")){
            	    		Toast.makeText(Login.this, "Logged in!", Toast.LENGTH_LONG).show();
            	    		Intent i = new Intent(Login.this, MainActivity.class);
                        	startActivity(i);
            	    		finish();
            	    	}else{
            	    		Toast.makeText(Login.this, "Error: " + response + "hi", Toast.LENGTH_LONG).show();            	    		
            	    	}
            	    }
            	});
            }
        });
		
	}

}
