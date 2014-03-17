package com.gunmania.pushexample;

import android.os.Bundle;
import android.preference.PreferenceActivity;

public class SettingActivity extends PreferenceActivity {

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		MainActivity.at.add(this);
        addPreferencesFromResource(R.xml.setting);
	}

}
