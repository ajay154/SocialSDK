/*
 * © Copyright IBM Corp. 2014
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at:
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */
package com.ibm.sbt.provisioning.sample.app.services;

import com.ibm.sbt.provisioning.sample.app.task.BSSProvisioning;
import com.ibm.sbt.services.client.smartcloud.bss.SubscriptionManagementService;
/**
 * This class represents a singleton that encapsulate an instance of
 * the class <code>com.ibm.sbt.services.client.smartcloud.bss.SubscriptionManagementService</code> 
 * */
public class Subscription {

	private static Subscription instance = null;
    private SubscriptionManagementService service ;
    
    private Subscription() {
    	this.service = new SubscriptionManagementService(BSSProvisioning.getBasicEndpoint());
    }
 
    public static synchronized Subscription getInstance() {
        if (instance == null) 
        	instance = new Subscription();
        return instance;
    }
	
    public SubscriptionManagementService getService() {
		return service;
	}
}
