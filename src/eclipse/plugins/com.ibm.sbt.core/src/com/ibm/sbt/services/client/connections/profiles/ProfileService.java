/*
 * � Copyright IBM Corp. 2012
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

package com.ibm.sbt.services.client.connections.profiles;

import java.io.UnsupportedEncodingException;

import java.net.URLEncoder;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.Set;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.w3c.dom.Document;
import com.ibm.commons.util.StringUtil;
import com.ibm.commons.xml.DOMUtil;
import com.ibm.commons.xml.XMLException;
import com.ibm.sbt.services.client.BaseService;
import com.ibm.sbt.services.client.ClientService;
import com.ibm.sbt.services.client.ClientServicesException;
import com.ibm.sbt.services.client.ClientService.Handler;
import com.ibm.sbt.services.client.connections.files.model.FileRequestParams;
import com.ibm.sbt.services.client.connections.files.model.Headers;
import com.ibm.sbt.services.client.connections.profiles.exception.ProfileServiceException;
import com.ibm.sbt.services.client.connections.profiles.utils.Messages;
import com.ibm.sbt.services.util.AuthUtil;

/**
 * ProfileService can be used to perform Profile Related operations. This is a dedicated Service for
 * Connections Profiles.
 * 
 * @Represents Connections ProfileService
 * @author Swati Singh
 */
public class ProfileService extends BaseService {

	static final String						sourceClass		= ProfileService.class.getName();
	static final Logger						logger			= Logger.getLogger(sourceClass);
	public static final String				ProfileBaseUrl	= "profiles";
	public static final String				seperator		= "/";
	private LRUCache lruCache;

	public static enum ProfilesAPI {
		GETPROFILE("profiles/atom/profile.do"), DELETEPROFILE("profiles/admin/atom/profileEntry.do"), ADDPROFILE(
				"profiles/admin/atom/profiles.do"), UPDATEPROFILE("profiles/atom/profileEntry.do"), UPDATEPROFILEPHOTO(
				"profiles/photo.do");

		private final String	url;

		ProfilesAPI(String url) {
			this.url = url;
		}

		public String getUrl() {
			return url;
		}

		@Override
		public String toString() {
			return this.url;
		}
	}

	/**
	 * Default Constructor - 0 argument constructor Calls the Constructor of BaseService Class.
	 */

	public ProfileService() {
		this(DEFAULT_ENDPOINT_NAME, DEFAULT_CACHE_SIZE);
		initializeCache(DEFAULT_CACHE_SIZE);
	}

	/**
	 * Constructor - 1 argument constructor
	 * 
	 * @param endpoint
	 *            Creates ProfileService with specified endpoint and a default CacheSize
	 */

	public ProfileService(String endpoint) {
		this(endpoint, DEFAULT_CACHE_SIZE);
		initializeCache(DEFAULT_CACHE_SIZE);
	}

	/**
	 * Constructor - 2 argument constructor
	 * 
	 * @param endpoint
	 * @param cacheSize
	 *            Creates ProfileService with specified values of endpoint and CacheSize
	 */

	public ProfileService(String endpoint, int cacheSize) {
		super(endpoint, cacheSize);
		initializeCache(cacheSize);
	}
	/**
	 * This method is used to initialize the LRU cache with given size. 
	 * 
	 * @param cacheSize
	 */
	
	private void initializeCache(int cacheSize){
		lruCache = new LRUCache(cacheSize);
	}

	/**
	 * This method is used to get the profile's of multiple user's. 
	 * 
	 * @param userIds
	 * @return Profile[]
	 */
	private Profile[] getProfiles(String[] userIds) throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "getProfiles", userIds);
		}
		Profile[] profiles = new Profile[userIds.length];
		int i = 0;
		if (userIds != null) {
			for (String userId : userIds) {
				if (userId != null) {
					profiles[i] = getProfile(userId);
					i++;
				} else // elementary NP handling. Setting the profile null;
				{
					profiles[i] = null;
					i++;
				}
			}
		}
		if (logger.isLoggable(Level.FINEST)) {
			String log = "call to retrive profiles successful";
			if (profiles != null) {
				for (Profile profile : profiles) {
					if (null == profile) {
						log = "Empty response from server for one of the requested profiles";
						break;
					}
				}
				log = "";
			}
			logger.exiting(sourceClass, "getProfiles", log);
		}
		return profiles;
	}
	
	/**
	 * This method is used by getProfiles method to fetch the Profile's of users, one at a time. this
	 * Single argument method, calls the getProfile method with 2 arguments, passing true to load the
	 * profile of the person..
	 *
	 * @return Profile
	 */
	public Profile getProfile(String userId) throws ProfileServiceException{
		return getProfile(userId, true);
	}

	/**
	 * Wrapper method , it makes the network call to fetch the Profile's of a user based on load parameter being true
	 * / false. This method can be called directly by passing the userId / Subscriber id of the user and a
	 * loaded argument as true / false
	 *
	 * @param userId
	 * @param loaded
	 * @return Profile
	 */
	public Profile getProfile(String userId, boolean loaded) throws ProfileServiceException {
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "getProfile", new Object[] { userId, loaded });
		}
		if (StringUtil.isEmpty(userId)) {
			throw new IllegalArgumentException(Messages.InvalidArgument_1);
		}
		Profile profile = new Profile(this, userId);
		if (loaded) {
			load(profile); // fetches profile content from server and populates
							// content of data member
		}

		if (logger.isLoggable(Level.FINEST)) {
			String log = "";
			if (profile.getId() != null) {
				log = Messages.GetProfileInfo_1;
			} else {
				log = Messages.GetProfileInfo_2;
			}
			logger.exiting(sourceClass, "getProfile", log);
		}
		return profile;
	}
	
	/**
	 * This method is used to get user's network contacts
	 * 
	 * @param profile
	 * @return Profile[] - array of network contacts profiles
	 */
	public Profile[] getColleagues(Profile profile) throws ProfileServiceException{
		return getColleagues(profile,null);
	}
	

	/**
	 * This method is used to get user's colleagues
	 * 
	 * @param profile
	 * @param parameters - list of query string parameters to pass to API
	 * @return Profile[] - array of network contacts profiles
	 */
	public Profile[] getColleagues(Profile profile, Map<String, String> parameters)
	throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "getColleagues", parameters);
		}
		if (profile == null) {
			throw new IllegalArgumentException(StringUtil.format("A null profile was passed"));
		}
		Document data = null;
		Profile[] colleagues = null;
		if(parameters == null)
			parameters = new HashMap<String, String>();
		String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
				ProfileType.GETCOLLEAGUES.getProfileType());
		if (isEmail(profile.getReqId())) {
			parameters.put(ProfileRequestParams.EMAIL, profile.getReqId());
		} else {
			parameters.put(ProfileRequestParams.USERID, profile.getReqId());
		}

		if(parameters.get("connectionType") != null)// this is to remove any other values put in by sample user in following
			parameters.remove("connectionType");	// mandatory parameters
		if(parameters.get("outputType") != null)
			parameters.remove("outputType");
		parameters.put("connectionType","colleague");
		parameters.put("outputType","profile");

		data = executeGet(url, parameters, ClientService.FORMAT_XML);
		colleagues = Converter.convertToProfiles(this, data);

		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "getColleagues");
		}
		return colleagues;

	}
	/**
	 * This method is used to get a person's report to chain
	 * 
	 * @param profile - profile of the user whose reporting chain is required
	 * @return Profile[] - array of network contacts profiles
	 * 
	 * @deprecated Do not use this method!
	 */

	
	private Profile[] getReportToChain(Profile profile)throws ProfileServiceException{
		return getReportToChain(profile,null);
		
	}
	/**
	 * This method is used to get a person's report to chain
	 * 
	 * @param profile - profile of the user whose reporting chain is required
	 * @param parameters - parameter map
	 * @return Profile[] - array of reporting chain profiles
	 * 
	 * @deprecated Do not use this method!
	 * 	 */
	private Profile[] getReportToChain (Profile profile, Map<String, String> parameters)throws ProfileServiceException{
		
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "getReportToChain", parameters);
		}
		if (profile == null) {
			throw new IllegalArgumentException(StringUtil.format("A null profile was passed"));
		}
		Document data = null;
		Profile[] reportingChain = null;
		if(parameters == null)
			parameters = new HashMap<String, String>();
		String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
				ProfileType.REPORTINGCHAIN.getProfileType());
		if (isEmail(profile.getReqId())) {
			parameters.put(ProfileRequestParams.EMAIL, profile.getReqId());
		} else {
			parameters.put(ProfileRequestParams.USERID, profile.getReqId());
		}
		data = executeGet(url, parameters, ClientService.FORMAT_XML);
		reportingChain = Converter.convertToProfiles(this, data);

		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "getReportToChain");
		}
		return reportingChain;
		
	}
	/**
	 * This method is used to get a person's direct reports
	 * 
	 * @param profile - profile of the user whose reporting chain is required
	 * @return Profile[] - array of direct reports profiles
	 * 
	 * @deprecated Do not use this method!
	 */
	private Profile[] getDirectReports(Profile profile) throws ProfileServiceException{
		return getDirectReports(profile,null);
		
	}
	/**
	 * This method is used to get a person's direct reports
	 * 
	 * @param profile - profile of the user whose reporting chain is required
	 * @param parameters - parameter map
	 * @return Profile[] - array of direct reports profiles
	 * 
	 * @deprecated Do not use this method!
	 */
	private Profile[] getDirectReports(Profile profile, Map<String, String> parameters)throws ProfileServiceException{
		
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "getDirectReports", parameters);
		}
		if (profile == null) {
			throw new IllegalArgumentException(StringUtil.format("A null profile was passed"));
		}
		Document data = null;
		Profile[] reportingChain = null;
		if(parameters == null)
			parameters = new HashMap<String, String>();

		String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
				ProfileType.DIRECTREPORTS.getProfileType());
		if (isEmail(profile.getReqId())) {
			parameters.put(ProfileRequestParams.EMAIL, profile.getReqId());
		} else {
			parameters.put(ProfileRequestParams.USERID, profile.getReqId());
		}
		data = executeGet(url, parameters, ClientService.FORMAT_XML);
		reportingChain = Converter.convertToProfiles(this, data);

		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "getDirectReports");
		}
		return reportingChain;
		
	}
	
	/**
	 * This method is used to Invite a person to become your colleague
	 * 
	 * @param profile - profile of the user to whom the invite needs to be sent	 * @param profile
	 * @return boolean - if invite is sent successfully then return true
	 */
	public boolean sendInvite(Profile profile)throws ProfileServiceException{
		String defaultInviteMsg = Messages.SendInviteMsg;
		return sendInvite(profile, defaultInviteMsg);
		
	}
	
	/**
	 * This method is used to send a Invite to person to be your connect
	 * 
	 * @param profile - profile of the user to whom the invite needs to be sent
	 * @param inviteMsg - message to the other user
	 * @return boolean - if invite is sent successfully then return true
	 */
	public boolean sendInvite(Profile profile, String inviteMsg)throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "getColleagues", inviteMsg);
		}
		if (profile == null) {
			throw new IllegalArgumentException(StringUtil.format("A null profile was passed"));
		}
		boolean returnVal = true;
		Map<String, String> parameters = new HashMap<String, String>();
		try {
			String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
					ProfileType.GETCOLLEAGUES.getProfileType());
			if (isEmail(profile.getReqId())) {
				parameters.put(ProfileRequestParams.EMAIL,profile.getReqId());
			} else {
				parameters.put(ProfileRequestParams.USERID, profile.getReqId());
			}
			parameters.put("connectionType","colleague");
			XMLProfilesPayloadBuilder builder = XMLProfilesPayloadBuilder.INSTANCE;
			Object content = builder.generateInviteRequestPayload(inviteMsg);
			getClientService().post(url, parameters, content);
		} catch (ClientServicesException e) {
			returnVal = false;
			if (logger.isLoggable(Level.SEVERE)) {
				logger.log(Level.SEVERE, "Error encountered while getting colleagues information", e);
			}
		}
		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "getColleagues");
		}
		return returnVal;

	}

	/**
	 * This method is used to accept a Invite 
	 * 
	 * @param connectionId - unique id of the
	 * @param title - message to the other user
	 * @return content - if invite is sent successfully then return true
	 * 
	 * @deprecated Do not use this method!
	 */
	private boolean acceptInvite(String connectionId, String title, String content)throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "acceptInvite", connectionId);
		}
		boolean returnVal = true;
		Map<String, String> parameters = new HashMap<String, String>();
		parameters.put(ProfileRequestParams.CONNECTIONID, connectionId);
		String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
				ProfileType.UPDATEINVITE.getProfileType());
		
		XMLProfilesPayloadBuilder builder = XMLProfilesPayloadBuilder.INSTANCE;
		Object payload = builder.generateAcceptInvitePayload(connectionId, title, content);
		try {
			getClientService().put(url, parameters, payload);
		} catch (ClientServicesException e) {
			returnVal = false;
			if (logger.isLoggable(Level.SEVERE)) {
				logger.log(Level.SEVERE, Messages.AcceptInviteError, e);
			}
		}
		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "acceptInvite");
		}
		return returnVal;
	}
	
	/**
	 * This method is used to delete/ignore a Invite 
	 * 
	 * @param connectionId - unique id of the connection
	 * 
	 * @deprecated Do not use this method!
	 */
	private boolean deleteInvite(String connectionId)throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "deleteInvite", connectionId);
		}
		Map<String, String> parameters = new HashMap<String, String>();
		parameters.put(ProfileRequestParams.CONNECTIONID, connectionId);
		String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
				ProfileType.UPDATEINVITE.getProfileType());
		boolean result = executeDelete(url, parameters);
		
		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "deleteInvite");
		}
		return result;
	}
	/**
	 * Wrapper method to update a User's profile photo
	 * 
	 * @param Profile
	 * @return boolean
	 */
	public boolean updateProfilePhoto(Profile profile) throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "updatePhoto", profile.toString());
		}
		if (profile == null) {
			throw new IllegalArgumentException(Messages.InvalidArgument_3);
		}
		boolean returnVal = true;

		Map<String, String> parameters = new HashMap<String, String>();
		if (isEmail(profile.getReqId())) {
			parameters.put(ProfileRequestParams.EMAIL, profile.getReqId());
		} else {
			parameters.put(ProfileRequestParams.USERID, profile.getReqId());
		}
		String filePath = profile.getFieldsMap().get("imageLocation");
		java.io.File file = new java.io.File(filePath);
		String name = filePath.substring(filePath.lastIndexOf('\\') + 1);

		int dot = name.lastIndexOf('.');
		String ext = null;
		if (dot > -1) {
			ext = name.substring(dot + 1); // add one for the dot!
		}
		if (StringUtil.isEmpty(ext)) {
			try {
				throw new Exception(Messages.UpdateProfileInfo_1);
			} catch (Exception e) {
				returnVal = false;
			}
		}
		Map<String, String> headers = new HashMap<String, String>();
		if (ext.equalsIgnoreCase("jpg")) {
			headers.put(Headers.ContentType, "image/jpeg");	// content-type should be image/jpeg for file extension - jpeg/jpg
		} else {
			headers.put(Headers.ContentType, "image/" + ext);
		}
		String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
				ProfileType.UPDATEPROFILEPHOTO.getProfileType());
		returnVal = executePut(url, parameters, headers, file, ClientService.FORMAT_NULL);

		profile.clearFieldsMap();
		removeProfileDataFromCache(profile.getReqId());
		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "updatePhoto" + returnVal);
		}
		return returnVal;
	}

	/**
	 * Wrapper method to update a User's profile
	 * 
	 * @param Profile
	 * @return boolean
	 */
	public boolean updateProfile(Profile profile) throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "update", profile);
		}
		if (profile == null) {
			throw new IllegalArgumentException(Messages.InvalidArgument_3);
		}
		boolean result = true;

		Map<String, String> parameters = new HashMap<String, String>();
		parameters.put(ProfileRequestParams.OUTPUT, "vcard");
		parameters.put(ProfileRequestParams.FORMAT, "full");
		Map<String, String> headers = new HashMap<String, String>();
		headers.put(Headers.ContentType, Headers.ATOM);
		if (isEmail(profile.getReqId())) {
			parameters.put(ProfileRequestParams.EMAIL, profile.getReqId());
		} else {
			parameters.put(ProfileRequestParams.USERID, profile.getReqId());
		}
		Object updateProfilePayload = profile.constructUpdateRequestBody();
		String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
				ProfileType.UPDATEPROFILE.getProfileType());
		result = executePut(url, parameters, headers, updateProfilePayload, ClientService.FORMAT_NULL);
		profile.clearFieldsMap();
		removeProfileDataFromCache(profile.getReqId());

		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "update");
		}

		return result;
	}

	/*
	 * Method to remove the user profile from cache.
	 * 
	 * @param userId
	 */
	protected void removeProfileDataFromCache(String userId) throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "removeProfileDataFromCache", userId);
		}
		if (isEmail(userId)) {
			String key;
			Set<String> keys = lruCache.getKeySet();
			Iterator<String> itr = keys.iterator();
			while (itr.hasNext()) {
				key = itr.next();
				Document data = lruCache.get(key);
				// check if email in profile object is same as input userId
				String email = "";
				try {
					email = DOMUtil.value(data, Profile.xpathMap.get("email"));
				} catch (XMLException e) {
					continue;
				}

				// cache hit
				if (StringUtil.equalsIgnoreCase(email, userId)) {
					lruCache.remove(key);
				}
			}
			// Cache miss

		} else {
			lruCache.remove(userId);
		}
		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "removeProfileDataFromCache");
		}

	}

	/*
	 * Method responsible for loading the profile.
	 * 
	 * @param profile
	 */
	protected void load(Profile profile) throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "load", profile);
		}
		// Do a cache lookup first. If cache miss, make a network call to get
		// Profile
		
		Document data = getProfileDataFromCache(profile.getReqId());
		if (data != null) {
			profile.setData(data);
		} else {

			Map<String, String> parameters = new HashMap<String, String>();
			if (isEmail(profile.getReqId())) {
				parameters.put("email", profile.getReqId());
			} else {
				parameters.put("userid", profile.getReqId());
			}
			Object result = null;
			try {
				String url = resolveProfileUrl(ProfileEntity.NONADMIN.getProfileEntityType(),
						ProfileType.GETPROFILE.getProfileType());
				result = endpoint.xhrGet(url, parameters, ClientService.FORMAT_XML);
			} catch (ClientServicesException e) {
				logger.log(Level.SEVERE, Messages.GetProfileInfo_3, e);
				result = null;
			}

			if (result != null) {
				profile.setData((Document) result);
				addProfileDataToCache(profile.getUniqueId(), (Document) result);
			} else {
				profile.setData(null);
			}

			if (logger.isLoggable(Level.FINEST)) {
				logger.exiting(sourceClass, "load");
			}
		}
	}

	/*
	 * Method to check if the Profile is cached. Calls findInCache to find for the profile in Cache.
	 * 
	 * @param userId
	 * @return Document
	 */
	private Document getProfileDataFromCache(String userId) {
		// this should return just the content ..xmldoc
		// should a have a common caching framework for all services
		Document data = null;
		if (isEmail(userId)) {
			data = findInCache(userId);
		} else {
			if(lruCache.hasKey(userId)){
				data = lruCache.get(userId);
			}
		}
		return data;
	}

	/*
	 * addProfileDataToCache() Method to cache the Profile of the User.
	 * 
	 * @param userId
	 * @param content
	 */
	private void addProfileDataToCache(String userId, Document content) {
	
		lruCache.put(userId, content);
	}

	/*
	 * Method to search the cache
	 * 
	 * @param userId
	 * @return Document
	 */
	private Document findInCache(String userId) {
		String key;
		Set<String> keys = lruCache.getKeySet();
		Iterator<String> itr = keys.iterator();
		while (itr.hasNext()) {
			key = itr.next();
			Document data = lruCache.get(key);
			// check if email in profile object is same as input userId
			String email = "";
			try {
				email = DOMUtil.value(data, Profile.xpathMap.get("email"), Profile.nameSpaceCtx);
			} catch (XMLException e) {
				continue;
			}
			// cache hit
			if (StringUtil.equalsIgnoreCase(email, userId)) {
				return data;
			}
		}
		// Cache miss
		return null;
	}

	/*
	 * Method responsible for generating appropriate REST URLs
	 * 
	 * @param ProfileEntity ( Ref Class : ProfileEntity )
	 * @param ProfileType ( Ref Class : ProfileType )
	 *
	 *  @return String
	 */
	public String resolveProfileUrl(String profileEntity, String profileType) {
		return resolveProfileUrl(profileEntity, profileType, null);
	}
	/*
	 * Method responsible for generating appropriate REST URLs
	 * 
	 * @param ProfileEntity ( Ref Class : ProfileEntity )
	 * @param ProfileType ( Ref Class : ProfileType )
	 * @param params : ( Ref Class : ProfileParams )
	 * @return String
	 */
	public String resolveProfileUrl(String profileEntity, String profileType, Map<String, String> params) {
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "resolveCommunityUrl", profileEntity + profileType);
		}

		StringBuilder proBaseUrl = new StringBuilder(ProfileBaseUrl);
		if (StringUtil.isEmpty(profileEntity)) {
			profileEntity = ProfileEntity.NONADMIN.getProfileEntityType(); // Default
																			// Entity
																			// Type
		}
		if (StringUtil.isEmpty(profileType)) {
			profileType = ProfileType.GETPROFILE.getProfileType(); // Default
																	// Profile
																	// Type
		}

		if (AuthUtil.INSTANCE.getAuthValue(endpoint).equalsIgnoreCase("oauth")) {
			if (profileEntity.equalsIgnoreCase(ProfileEntity.NONADMIN.getProfileEntityType())) {
				proBaseUrl.append(seperator).append("oauth");
			}
		}
		if (profileEntity.equalsIgnoreCase("")) {// if it is non admin API then
													// no need to append anythin
			proBaseUrl.append(seperator).append(profileType);
		} else {
			proBaseUrl.append(profileEntity).append(seperator).append(profileType);

		}

		// Add required parameters
		if (null != params) {
			if (params.size() > 0) {
				proBaseUrl.append("?");
				boolean setSeperator = false;
				for (Map.Entry<String, String> param : params.entrySet()) {
					if (setSeperator) {
						proBaseUrl.append("&");
					}
					String paramvalue = "";
					try {
						paramvalue = URLEncoder.encode(param.getValue(), "UTF-8");
					} catch (UnsupportedEncodingException e) {}
					proBaseUrl.append(param.getKey() + "=" + paramvalue);
					setSeperator = true;
				}
			}
		}

		if (logger.isLoggable(Level.FINEST)) {
			logger.log(Level.FINEST, Messages.ProfileInfo_7 + proBaseUrl.toString());
		}
		return proBaseUrl.toString();
	}
	
	/**
	 * executeGet
	 * 
	 * @param uri
	 * @param params
	 * @throws ProfileServiceException
	 */
	protected Document executeGet(String uri, Map<String, String> params, Handler format)
	throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "executeGet", new Object[] { uri, params });
		}
		Document data = null;
		try {
			data = (Document) getClientService().get(uri, params);
		} catch (ClientServicesException e) {
			if (logger.isLoggable(Level.SEVERE)) {
				logger.log(Level.SEVERE, Messages.ProfileServiceException_1 + "executeGet()", e);
			}
			throw new ProfileServiceException(e);
		}
		if (data == null) {
			return null;
		}
		return data;
	}

	/**
	 * executeDelete
	 * 
	 * @param uri
	 * @param params
	 * @throws ProfileServiceException
	 */
	protected boolean executeDelete(String uri, Map<String, String> params)
	throws ProfileServiceException{
		if (logger.isLoggable(Level.FINEST)) {
			logger.exiting(sourceClass, "executeDelete", new Object[] { uri, params });
		}
		boolean result = true;
		try {
			getClientService().delete(uri, params);
		} catch (ClientServicesException e) {
			result = false;
			if (logger.isLoggable(Level.SEVERE)) {
				logger.log(Level.SEVERE, Messages.ProfileServiceException_1 + "executeDelete()", e);
			}
			throw new ProfileServiceException(e);
		}
		
		return result;
	}
	
	/**
	 * executePut
	 * 
	 * @param requestUri
	 *            - api to be executed.
	 * @param params
	 *            - Map of Parameters. See {@link FileRequestParams} for possible values.
	 * @param headers
	 *            - Map of Headers. See {@link Headers} for possible values.
	 * @param payload
	 *            - Document which is passed directly as requestBody to the execute request. This method is
	 *            used to update the metadata/content of File in Connections.
	 * @return boolean
	 * @throws ProfileServiceException
	 */

	public boolean executePut(String requestUri, Map<String, String> parameters,
			Map<String, String> headers, Object payload, Handler format) throws ProfileServiceException {
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "executePut");
		}
		boolean success = true;
		try {
			getClientService().put(requestUri, parameters, headers, payload, format);
		} catch (ClientServicesException e) {
			success = false;
			logger.log(Level.SEVERE, Messages.ProfileServiceException_1 + "executePut()", e);
			throw new ProfileServiceException(e);
		}
		return success;
	}
	
	/**
	 * executePost
	 * 
	 * @param requestUri
	 *            - api to be executed.
	 * @param params
	 *            - Map of Parameters. See {@link FileRequestParams} for possible values.
	 * @param headers
	 *            - Map of Headers. See {@link Headers} for possible values.
	 * @param payload
	 *            - Document which is passed directly as requestBody to the execute request. This method is
	 *            used to update the metadata/content of File in Connections.
	 * @return boolean
	 * @throws ProfileServiceException
	 */

	public boolean executePost(String requestUri, Map<String, String> parameters,
			Map<String, String> headers, Object payload, Handler format) throws ProfileServiceException {
		if (logger.isLoggable(Level.FINEST)) {
			logger.entering(sourceClass, "executePut");
		}
		boolean success = true;
		try {
			getClientService().post(requestUri, parameters, headers, payload, format);
		} catch (ClientServicesException e) {
			success = false;
			logger.log(Level.SEVERE, Messages.ProfileServiceException_1 + "executePost()", e);
			throw new ProfileServiceException(e);
		}
		return success;
	}

	/*
	 * Method to check if the userid is email. Current check is based on finding @ in the userid.
	 * 
	 * @param userId
	 * @return boolean
	 */
	protected boolean isEmail(String userId) {
		if (StringUtil.isEmpty(userId)) {
			return false;
		}
		return userId.contains("@");
	}

}