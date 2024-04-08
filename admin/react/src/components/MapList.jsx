import {
  deleteCustomMap,
  deleteCustomNodes,
  deleteWpNodes,
  getCustomMap,
  getCustomNodes,
  getCustomUnavailableNodes,
  getProxyData,
  saveCustomNodes,
  updateCustomMapLastUpdated, updateCustomNodesAuthority, updateCustomNodesStatus
} from '../utils/api'
import PropTypes from 'prop-types'
import { formDefaults } from '../data/formDefaults'
import ProgressBar from './ProgressBar'
import { useState } from 'react'
import {addDefaultScheme, checkAuthority, generateAuthorityMap} from '../utils/domainAuthority'
import {fetchProfileData, validateProfileData} from '../utils/fetchProfile'

export default function MapList({
  maps,
  getMaps,
  setFormData,
  setIsEdit,
  setIsMapSelected,
  isLoading,
  setIsLoading,
  setIsRetrieving,
  setProfileList,
  setCurrentTime,
  setProgress,
  progress,
  setDeletedProfiles,
  setUnauthorizedProfiles
}) {
  const [isPopupOpen, setIsPopupOpen] = useState(false)
  const [mapIdToDelete, setMapIdToDelete] = useState(null)

  const handleCreate = () => {
    setFormData(formDefaults)
    setIsEdit(false)
    setIsMapSelected(true)
    setIsRetrieving(false)
    setProfileList([])
    setCurrentTime('')
    setDeletedProfiles([])
    setUnauthorizedProfiles([])
  }

  /**
   * Retrieve data from the index service with timestamp, which means get updated profiles only.
   * There are 5 types of profiles:
   * 1. new profiles - profiles that are not in the nodes table
   * 2. updated profiles - profiles that are in the nodes table and have updates
   * 3. unavailable profiles - profiles that unavailable in nodes table needs to check again to see if it's available now
   * 4. deleted profiles - profiles status is marked as "deleted" in the index service
   * 5. unauthorized profiles - if a profile's domain authority is false, and there are no other available profiles, it will be marked as unauthorized. Otherwise, unauthorized profiles should be showed in the profiles list.
   */
  const handleRetrieve = async (mapId, requestUrl, tagSlug) => {
    setIsLoading(true)
    setIsRetrieving(true)
    setDeletedProfiles([])
    setUnauthorizedProfiles([])

    try {
      // Get map information from WordPress DB
      const mapResponse = await getCustomMap(mapId)
      const mapResponseData = await mapResponse.json()
      if (!mapResponse.ok) {
        alert(
          `Map Error: ${mapResponse.status} ${JSON.stringify(mapResponseData)}`
        )
        return
      }

      // Get map last_updated
      const mapLastUpdated = mapResponseData?.last_updated
      if (mapLastUpdated) {
        requestUrl += `&last_updated=${mapLastUpdated}`
      }

      // Before retrieving data, get the current time
      const currentTime = new Date().getTime().toString()
      setCurrentTime(currentTime)

      // Get data from requestUrl - Index URL + Query URL
      const response = await fetch(requestUrl)
      const responseData = await response.json()
      if (!response.ok) {
        alert(
          `Retrieve Error: ${response.status} ${JSON.stringify(responseData)}`
        )
        return
      }

      // Get the profiles from requestUrl
      const profiles = responseData.data

      // Get unavailable profiles from WordPress DB
      let unavailableProfiles = []
      const unavailableNodesResponse = await getCustomUnavailableNodes(mapId)
      const unavailableNodesResponseData = await unavailableNodesResponse.json()
      if (!unavailableNodesResponse.ok) {
        if (unavailableNodesResponse.status !== 404) {
          alert(
            `Unavailable Nodes Error: ${
              unavailableNodesResponse.status
            } ${JSON.stringify(unavailableNodesResponseData)}`
          )
          return
        }
      } else {
        unavailableProfiles = unavailableNodesResponseData
      }

      // Define variables, dataWithIds is the final profiles list
      let dataWithoutIds = []
      let deletedProfiles = []
      let unauthorizedProfiles = []

      // Setup progress bar
      const progressStep = 100 / (profiles.length + unavailableProfiles.length)
      let progress = 0

      if (profiles.length > 0) {
        // Get all nodes from WordPress DB to generate a primary url Map
        const allNodesResponse = await getCustomNodes(mapId)
        let allNodesResponseData = await allNodesResponse.json()
        if (!allNodesResponse.ok) {
          alert(
            `Get Nodes Error: ${allNodesResponse.status} ${JSON.stringify(
              allNodesResponseData
            )}`
          )
          return
        }

        const authorityCheckingList = generateAuthorityMap(
          allNodesResponseData,
          'profile_data.primary_url',
          'profile_url'
        )

        // Loop through profiles which is from Index Service
        for (let i = 0; i < profiles.length; i++) {
          // Update progress
          progress = (i + 1) * progressStep
          if (progress > 100) {
            setProgress(100)
          } else {
            setProgress(progress)
          }

          const profile = profiles[i]
          let profileData = ''

          // Get single node information
          const customNodesResponse = await getCustomNodes(
            mapId,
            profile.profile_url
          )
          const customNodesResponseData = await customNodesResponse.json()
          if (!response.ok && response.status !== 404) {
            alert(
              `Get Profile Error: ${
                customNodesResponse.status
              } ${JSON.stringify(customNodesResponseData)}`
            )
            continue
          }

          let profileObject = {
            profile_data: profileData,
            index_data: profile,
            data: {
              map_id: mapId,
              tag_slug: tagSlug,
              is_available: 1,
              has_authority: 1
            }
          }

          // Handle deleted profiles
          if (profile.status === 'deleted') {
            if (customNodesResponse.status === 404) {
              continue
            }

            // If customNodesResponse is not 404, save information into profileObject
            profileObject.data.node_id = customNodesResponseData[0].id
            profileObject.data.post_id = customNodesResponseData[0].post_id
            profileObject.profile_data = customNodesResponseData[0].profile_data

            // Request to delete WP Nodes
            if (profileObject.data.post_id) {
              const deleteNodeResponse = await deleteWpNodes(
                profileObject.data.post_id
              )
              if (!deleteNodeResponse.ok) {
                const deleteNodeResponseData = await deleteNodeResponse.json()
                alert(
                  `Delete Profile Error: ${
                    deleteNodeResponse.status
                  } ${JSON.stringify(deleteNodeResponseData)}`
                )
                continue
              }
            }

            // Delete node from nodes table - no need to check the node_id because it's mandatory field in the table
            const deleteResponse = await deleteCustomNodes(
              profileObject.data.node_id
            )

            if (!deleteResponse.ok) {
              const deleteResponseData = await deleteResponse.json()
              alert(
                `Delete Node Error: ${deleteResponse.status} ${JSON.stringify(
                  deleteResponseData
                )}`
              )
              continue
            }

            // Put deleted profile in list
            deletedProfiles.push(profileObject)
            continue
          }

          // Handle new profiles and updated profiles
          // Check the profile_url is available or not
          let { profileData: fetchedProfileData, fetchProfileError } =
            await fetchProfileData(profile.profile_url)
          profileData = fetchedProfileData

          // If profileData is empty, then it's not available
          if (profileData === '') {
            profileObject.data.is_available = 0
            profileObject.data.unavailable_message = fetchProfileError
          } else {
            // Validate the profile data before adding to the list
            const isValid = await validateProfileData(profileData, mapResponseData?.index_url)
            if (!isValid) {
              profileObject.data.is_available = 0
              profileObject.data.unavailable_message = 'Invalid Profile Data'
            }
          }

          // Give extra data to profile
          profileObject.profile_data = profileData
          profileObject.data.status = 'new'
          profileObject.data.extra_notes = ''

          // Set domain authority
          if (
            profile?.profile_url &&
            profile?.primary_url && authorityCheckingList.has(addDefaultScheme(profile?.primary_url))
          ) {
            profileObject.data.has_authority = checkAuthority(
              profile.primary_url,
              profile.profile_url
            )
          }

          // If WP nodes is 404, it's new profile. If WP nodes is not 404, it's updated profile.
          if (customNodesResponse.status === 404) {
            if (!profileObject.data.has_authority) {
              profileObject.data.status = 'ignore'
            }
            const profileResponse = await saveCustomNodes(profileObject)
            if (!profileResponse.ok) {
              const profileResponseData = await profileResponse.json()

              // if the profile_url length is too long, alert the user and skip the profile
              if (
                profileResponse.status === 400 &&
                profileResponseData?.code === 'profile_url_length_exceeded'
              ) {
                alert(
                  `profile_url_length_exceeded: ${profileObject.index_data.profile_url}`
                )
                continue
              }

              alert(
                `Unable to save profiles to wpdb, errors: ${
                  profileResponse.status
                } ${JSON.stringify(
                  profileResponseData
                )}. Please delete the map and try again.`
              )
              return
            }

            // If a profile is new and has no domain authority, put it in unauthorizedProfiles
            if (!profileObject.data.has_authority) {
              unauthorizedProfiles.push(profileObject)
              continue
            }
          } else {
            // Update information to profileObject from Nodes table
            profileObject.data.node_id = customNodesResponseData[0].id
            profileObject.data.post_id = customNodesResponseData[0].post_id

            // Handle domain authority first, because if a profile has no domain authority, it should be ignored
            if (!profileObject.data.has_authority) {
              // if a published profile has no domain authority, mark it as ignored
              profileObject.data.status = 'ignore'
              if (customNodesResponseData[0].status === 'publish') {
                // Delete the profile from WP nodes
                const deleteWPNodeResponse = await deleteWpNodes(
                  customNodesResponseData[0].post_id
                )
                if (!deleteWPNodeResponse.ok) {
                  const deleteWPNodeResponseData = await deleteWPNodeResponse.json()
                  alert(
                    `Delete Profile Error: ${
                      deleteWPNodeResponse.status
                    } ${JSON.stringify(deleteWPNodeResponseData)}`
                  )
                  continue
                }
              }
              // Update the profile in nodes table
              const updateResponse = await updateCustomNodesStatus(profileObject)
              if (!updateResponse.ok) {
                const updateResponseData = await updateResponse.json()
                alert(
                  `Update Node Status Error: ${updateResponse.status} ${JSON.stringify(
                    updateResponseData
                  )}`
                )
                continue
              }

              // if a profile is already ignore, don't add it to the list
              if (customNodesResponseData[0].status !== 'ignore') {
                unauthorizedProfiles.push(profileObject)
              }
              continue
            }

            // Showing the updates if there is any
            profileObject.data.status = customNodesResponseData[0].status

            if (
              customNodesResponseData[0].last_updated !==
              profile.last_updated.toString()
            ) {
              profileObject.data.extra_notes = 'see updates'
            }

            // Ignore profiles whose status is not 'new' and which do not have any updates - I want to show profiles with updates only
            if (
              customNodesResponseData[0].status !== 'new' &&
              profileObject.data.extra_notes !== 'see updates'
            ) {
              continue
            }
          }

          dataWithoutIds.push(profileObject)
        }
      }

      // Domain Authority Checking: Update the other data after we have new data
      // Get all nodes from WordPress DB to generate a primary url Map
      const allNodesResponse = await getCustomNodes(mapId)
      let allNodesResponseData = await allNodesResponse.json()
      if (!allNodesResponse.ok) {
        alert(
          `Get Nodes Error: ${allNodesResponse.status} ${JSON.stringify(
            allNodesResponseData
          )}`
        )
        return
      }

      const authorityCheckingList = generateAuthorityMap(
        allNodesResponseData,
        'profile_data.primary_url',
        'profile_url'
      )

      for (let i = 0; i < allNodesResponseData.length; i++) {
        const node = allNodesResponseData[i]
        if (node?.id && node.profile_data.primary_url && node.profile_url) {
          let hasAuthority = 1
          if (authorityCheckingList.has(addDefaultScheme(node?.profile_data?.primary_url))) {
            hasAuthority = checkAuthority(
              node.profile_data.primary_url,
              node.profile_url
            )
          }
          const updateResponse = await updateCustomNodesAuthority(node.id.toString(), hasAuthority)
          if (!updateResponse.ok) {
            const updateResponseData = await updateResponse.json()
            alert(
              `Update Node Status Error: ${updateResponse.status} ${JSON.stringify(
                updateResponseData
              )}`
            )
          }
          // Construct the profileObject and put it in unauthorizedProfiles
          let profileObject = {
            profile_data: node.profile_data,
            index_data: {
              profile_url: node.profile_url,
            },
            data: {
              node_id: node.id,
              post_id: node.post_id,
              map_id: node.map.id,
              is_available: node.is_available,
              unavailable_message: node.unavailable_message,
              has_authority: hasAuthority,
              last_updated: node.last_updated,
              status: node.status,
              tag_slug: tagSlug,
            }
          }
          if (!profileObject.data.has_authority) {
            // If a published profile has no domain authority, mark it as ignored
            profileObject.data.status = 'ignore'
            if (node.status === 'publish') {
              // Delete the profile from WP nodes
              const deleteWPNodeResponse = await deleteWpNodes(
                profileObject.data.post_id
              )
              if (!deleteWPNodeResponse.ok) {
                const deleteWPNodeResponseData = await deleteWPNodeResponse.json()
                alert(
                  `Delete Profile Error: ${
                    deleteWPNodeResponse.status
                  } ${JSON.stringify(deleteWPNodeResponseData)}`
                )
                continue
              }
            }
            // Update the profile in nodes table
            const updateResponse = await updateCustomNodesStatus(profileObject)
            if (!updateResponse.ok) {
              const updateResponseData = await updateResponse.json()
              alert(
                `Update Node Status Error: ${updateResponse.status} ${JSON.stringify(
                  updateResponseData
                )}`
              )
              continue
            }

            // If a profile is already ignore, don't add it to the list
            if (node.status !== 'ignore') {
              unauthorizedProfiles.push(profileObject)
            }
          } else {
            // From NAP to AP
            // I can use data.node_id to find the matched profile in dataWithoutIds
            let matchedProfile = dataWithoutIds.find(
              profile => profile.data.node_id === node.id
            )
            if (matchedProfile) {
              if (hasAuthority !== matchedProfile.data.has_authority) {
                // replace dataWithoutIds with matchedProfile
                dataWithoutIds = matchedProfile.map(item => {
                  if (item.data.node_id === node.id) {
                    item.data.has_authority = hasAuthority
                  }
                  return item
                })
              }
            } else {
              hasAuthority = hasAuthority === 1
              if (node.has_authority !== hasAuthority) {
                dataWithoutIds.push(profileObject)
              }
            }
          }
        }
      }

      // remove duplicates from unauthorizedProfiles
      unauthorizedProfiles = unauthorizedProfiles.filter((item, index, self) =>
        self.findIndex(t => t.data.node_id === item.data.node_id) === index);

      // Handle unavailable profiles
      if (unavailableProfiles.length > 0) {
        for (let i = 0; i < unavailableProfiles.length; i++) {
          // Update progress
          progress += progressStep
          if (progress > 100) {
            setProgress(100)
          } else {
            setProgress(progress)
          }

          const profile = unavailableProfiles[i]

          // Check this profile is already in the list or not
          const isProfileInList = dataWithoutIds.find(
            profileInList => profileInList.data.node_id === profile.id
          )
          if (isProfileInList) {
            continue
          }

          // Fetch the data, if it's available now, add it to the list
          let profile_data = ''
          if (profile.profile_url) {
            try {
              const response = await getProxyData(profile.profile_url)
              if (response.ok) {
                profile_data = await response.json()
              }
            } catch (error) {
              // If there is an error, don't add it to the list
              continue
            }
          }

          if (profile_data !== '') {
            // Validate the profile data before adding to the list
            const isValid = await validateProfileData(profile_data, mapResponseData?.index_url)
            if (!isValid) {
              // If the profile data is invalid, don't add it to the list
              continue
            }

            let profileObject = {
              profile_data: profile_data,
              index_data: profile,
              data: {
                map_id: mapId,
                tag_slug: tagSlug,
                node_id: profile.id,
                status: profile.status,
                is_available: 1,
                unavailable_message: '',
                has_authority: profile.has_authority
              }
            }
            dataWithoutIds.push(profileObject)
          }
        }
      }

      if (
        deletedProfiles.length === 0 &&
        dataWithoutIds.length === 0 &&
        unauthorizedProfiles.length === 0
      ) {
        setProfileList([])
        setIsMapSelected(false)
        setIsRetrieving(false)
        setIsLoading(false)
        alert(`No updated profiles found.`)
        return
      }

      // If it only has deleted profiles and unauthorized profiles, update map timestamp and set `setIsMapSelected` to false to return to the map list
      if (
        (deletedProfiles.length > 0 || unauthorizedProfiles.length > 0) &&
        dataWithoutIds.length === 0
      ) {
        const mapResponse = await updateCustomMapLastUpdated(mapId, currentTime)
        if (!mapResponse.ok) {
          const mapResponseData = await mapResponse.json()
          alert(
            `Map Error: ${mapResponse.status} ${JSON.stringify(
              mapResponseData
            )}`
          )
        }
        setCurrentTime('')
        setIsMapSelected(false)
      } else {
        setIsMapSelected(true)
      }

      if (dataWithoutIds.length > 0 && unauthorizedProfiles.length > 0) {
        dataWithoutIds = dataWithoutIds.concat(unauthorizedProfiles)
      }

      // loop through dataWithoutIds to add ids
      const dataWithIds = []
      let currentId = 1
      for (let profile of dataWithoutIds) {
        profile.id = currentId
        currentId++
        dataWithIds.push(profile)
      }

      setDeletedProfiles(deletedProfiles)
      setUnauthorizedProfiles(unauthorizedProfiles)
      setProfileList(dataWithIds)
    } catch (error) {
      alert(`Retrieve node error: ${error}`)
    } finally {
      setIsLoading(false)
      setProgress(0)
      window.scrollTo(0, 0);
    }
  }

  const handleEditNodes = async mapId => {
    setIsLoading(true)
    setIsRetrieving(true)
    setIsMapSelected(true)
    setDeletedProfiles([])
    setUnauthorizedProfiles([])

    try {
      // Get nodes from WP
      const response = await getCustomNodes(mapId)
      const profiles = await response.json()
      if (!response.ok) {
        alert(`Nodes Error: ${response.status} ${JSON.stringify(profiles)}`)
        return
      }

      let currentId = 1
      let dataWithIds = []
      for (let profile of profiles) {
        let profileObject = {
          id: currentId,
          profile_data: profile.profile_data,
          index_data: {
            profile_url: profile.profile_url,
            last_updated: profile.last_updated
          },
          data: {
            map_id: mapId,
            node_id: profile.id,
            post_id: profile.post_id,
            tag_slug: profile.map.tag_slug,
            status: profile.status,
            is_available: profile.is_available,
            unavailable_message: profile.unavailable_message,
            has_authority: profile.has_authority
          }
        }

        currentId++
        dataWithIds.push(profileObject)
      }
      setProfileList(dataWithIds)
    } catch (error) {
      alert(`Edit nodes error: ${error}`)
    } finally {
      setIsLoading(false)
    }
  }

  const handleEditMap = async mapId => {
    setIsEdit(true)
    setIsMapSelected(true)
    setDeletedProfiles([])
    setUnauthorizedProfiles([])
    setProfileList([])
    const map = maps.find(map => map.id === mapId)
    setFormData({
      map_id: map.id,
      map_name: map.name,
      map_center_lat: map.map_center_lat,
      map_center_lon: map.map_center_lon,
      map_scale: map.map_scale
    })
  }

  const handleDelete = mapId => {
    setIsPopupOpen(true)
    setMapIdToDelete(mapId)
  }

  const handleDeleteConfirm = async () => {
    setIsPopupOpen(false)
    setIsLoading(true)
    setDeletedProfiles([])
    setUnauthorizedProfiles([])

    if (!mapIdToDelete) {
      alert(`Delete Map ID is missing.`)
      return
    }

    try {
      const mapResponse = await deleteCustomMap(mapIdToDelete)
      if (!mapResponse.ok) {
        const mapResponseData = await mapResponse.json()
        alert(
          `Map Error: ${mapResponse.status} ${JSON.stringify(mapResponseData)}`
        )
      }
    } catch (error) {
      alert(`Delete map error: ${error}`)
    } finally {
      await getMaps().then(() => {
        setProfileList([])
        setFormData(formDefaults)
        setIsLoading(false)
        setMapIdToDelete(null)
      })
    }
  }

  const handleDeleteCancel = () => {
    setIsPopupOpen(false)
    setMapIdToDelete(null)
    window.scrollTo(0, 0)
  }

  return (
    <div>
      {!isLoading && (
        <div>
          <button
            className={`mb-4 max-w-fit rounded-full bg-orange-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-orange-400 active:scale-90 disabled:opacity-75 ${
              isLoading ? 'cursor-not-allowed opacity-50' : ''
            }`}
            onClick={() => handleCreate()}
          >
            Create Map
          </button>
          {maps.length > 0 && (
            <div>
              <p className="mt-4 text-base">
                Add a shortcode into a page or post. More information about the
                parameters for shortcodes can be found{' '}
                <a
                  href="https://docs.murmurations.network/developers/wp-aggregator.html#shortcodes"
                  target="_blank"
                  className="text-blue-500 underline"
                  rel="noreferrer"
                >
                  in the docs
                </a>
                .
              </p>
              <p className="mt-2 text-base">
                Click the <em className="font-semibold">Update Nodes</em> button
                to check for updates to the nodes in that map.{' '}
                <em className="font-semibold">Manage Nodes</em> enables you to
                change the published status of nodes without checking for
                updates.
              </p>
            </div>
          )}
          {maps.length > 0 ? (
            maps.map((map, index) => (
              <div className="mt-4 rounded bg-white p-4 shadow-md" key={index}>
                <h2 className="mb-2 text-xl font-semibold">{map.name}</h2>
                <p>
                  <strong>Query URL:</strong>{' '}
                  <a
                    className="text-blue-500 underline"
                    href={map.index_url + map.query_url}
                    target="_blank"
                    rel="noreferrer"
                  >
                    {map.index_url + map.query_url}
                  </a>
                </p>
                <p>
                  <strong>Shortcode:</strong> [murmurations_map tag_slug=&quot;
                  {map.tag_slug}&quot; height=&quot;60&quot;
                  width=&quot;100&quot; view=&quot;map&quot;]
                </p>
                <p>
                  <strong>Map Center:</strong>{' '}
                  {map.map_center_lat + ', ' + map.map_center_lon}
                </p>
                <p>
                  <strong>Map Scale:</strong> {map.map_scale}
                </p>
                <p>
                  <strong>Created At:</strong> {map.created_at}
                </p>
                <p>
                  <strong>Updated At:</strong> {map.updated_at}
                </p>
                <div className="mt-4 box-border flex flex-row flex-wrap justify-between xl:min-w-max">
                  <button
                    className={`mx-2 my-1 max-w-fit rounded-full bg-yellow-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-yellow-400 active:scale-90 disabled:opacity-75 ${
                      isLoading ? 'cursor-not-allowed opacity-50' : ''
                    }`}
                    onClick={() =>
                      handleRetrieve(
                        map.id,
                        map.index_url + map.query_url,
                        map.tag_slug
                      )
                    }
                  >
                    Update Nodes
                  </button>
                  <button
                    className={`mx-2 my-1 max-w-fit rounded-full bg-yellow-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-yellow-400 active:scale-90 disabled:opacity-75 ${
                      isLoading ? 'cursor-not-allowed opacity-50' : ''
                    }`}
                    onClick={() => handleEditNodes(map.id)}
                  >
                    Manage Nodes
                  </button>
                  <button
                    className={`mx-2 my-1 max-w-fit rounded-full bg-yellow-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-yellow-400 active:scale-90 disabled:opacity-75 ${
                      isLoading ? 'cursor-not-allowed opacity-50' : ''
                    }`}
                    onClick={() => handleEditMap(map.id)}
                  >
                    Edit Map
                  </button>
                  <button
                    className={`mx-2 my-1 max-w-fit rounded-full bg-red-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-red-400 active:scale-90 disabled:opacity-75 ${
                      isLoading ? 'cursor-not-allowed opacity-50' : ''
                    }`}
                    onClick={() => handleDelete(map.id)}
                  >
                    Delete Map
                  </button>
                </div>
              </div>
            ))
          ) : (
            <p className="mt-4 text-base">
              Create your first map or directory by clicking the Create Map
              button above.
            </p>
          )}
        </div>
      )}

      {isLoading && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="w-1/2 rounded bg-yellow-100 p-8 shadow-xl">
            <p className="mb-4 text-center text-2xl">Loading...</p>
            {<ProgressBar progress={progress} />}
            <p className="mt-4 text-center text-xl">
              Murmurations is an unfunded volunteer-led project.
              <br />
              Please consider{' '}
              <a
                href="https://opencollective.com/murmurations"
                target="_blank"
                className="text-blue-500 underline"
                rel="noreferrer"
              >
                making a donation
              </a>{' '}
              to support development.
            </p>
          </div>
        </div>
      )}
      {isPopupOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="rounded bg-red-100 p-8 shadow-xl">
            <p className="text-xl">
              Are you sure you want to delete this map and all of its data?
            </p>
            <div className="mt-4 flex justify-center">
              <button
                onClick={handleDeleteConfirm}
                className="mr-4 rounded bg-red-500 px-4 py-2 text-lg font-bold text-white hover:bg-red-400"
              >
                Confirm
              </button>
              <button
                onClick={handleDeleteCancel}
                className="rounded bg-gray-500 px-4 py-2 text-lg font-bold text-white hover:bg-gray-400"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

MapList.propTypes = {
  maps: PropTypes.array.isRequired,
  getMaps: PropTypes.func.isRequired,
  setFormData: PropTypes.func.isRequired,
  setIsEdit: PropTypes.func.isRequired,
  setIsMapSelected: PropTypes.func.isRequired,
  isLoading: PropTypes.bool.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  setIsRetrieving: PropTypes.func.isRequired,
  setProfileList: PropTypes.func.isRequired,
  setCurrentTime: PropTypes.func.isRequired,
  setProgress: PropTypes.func.isRequired,
  progress: PropTypes.number.isRequired,
  setDeletedProfiles: PropTypes.func.isRequired,
  setUnauthorizedProfiles: PropTypes.func.isRequired
}
