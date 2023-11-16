import {
  deleteCustomMap,
  deleteCustomNodes,
  deleteWpNodes,
  getCustomMap,
  getCustomNodes,
  getCustomUnavailableNodes,
  saveCustomNodes,
  updateCustomMapLastUpdated
} from '../utils/api'
import PropTypes from 'prop-types'
import { formDefaults } from '../data/formDefaults'
import { useState } from 'react'

export default function MapList({
  maps,
  getMaps,
  setFormData,
  setIsEdit,
  isLoading,
  setIsLoading,
  setIsRetrieving,
  setProfileList,
  setCurrentTime,
  setProgress,
  setDeletedProfiles
}) {
  const [isPopupOpen, setIsPopupOpen] = useState(false)
  const [mapIdToDelete, setMapIdToDelete] = useState(null)

  const handleCreate = () => {
    setFormData(formDefaults)
    setIsEdit(false)
    setIsRetrieving(false)
    setProfileList([])
    setCurrentTime('')
    setDeletedProfiles([])
  }

  const handleRetrieve = async (mapId, requestUrl, tagSlug) => {
    setIsLoading(true)
    setIsRetrieving(true)
    setDeletedProfiles([])

    try {
      // get map data from WP
      const mapResponse = await getCustomMap(mapId)
      const mapResponseData = await mapResponse.json()
      if (!mapResponse.ok) {
        alert(
          `Map Error: ${mapResponse.status} ${JSON.stringify(mapResponseData)}`
        )
        return
      }

      // get map last_updated
      const mapLastUpdated = mapResponseData.last_updated
      if (mapLastUpdated) {
        requestUrl += `&last_updated=${mapLastUpdated}`
      }

      // get data from requestUrl - Index URL + Query URL
      const currentTime = new Date().getTime().toString()
      setCurrentTime(currentTime)
      const response = await fetch(requestUrl)
      const responseData = await response.json()
      if (!response.ok) {
        alert(
          `Retrieve Error: ${response.status} ${JSON.stringify(responseData)}`
        )
        return
      }
      const profiles = responseData.data

      // get unavailable profiles
      let unavailableProfiles = []
      const unavailableNodesResponse = await getCustomUnavailableNodes(mapId)
      const unavailableNodesResponseData = await unavailableNodesResponse.json()
      if (
        !unavailableNodesResponse.ok &&
        unavailableNodesResponse.status !== 404
      ) {
        alert(
          `Unavailable Nodes Error: ${
            unavailableNodesResponse.status
          } ${JSON.stringify(unavailableNodesResponseData)}`
        )
        return
      } else {
        unavailableProfiles = unavailableNodesResponseData
      }

      let dataWithIds = []
      let deletedProfiles = []
      let currentId = 1
      const progressStep = 100 / (profiles.length + unavailableProfiles.length)
      let progress = 0

      if (profiles.length > 0) {
        for (let i = 0; i < profiles.length; i++) {
          // update progress
          progress = (i + 1) * progressStep
          if (progress > 100) {
            setProgress(100)
          } else {
            setProgress(progress)
          }

          const profile = profiles[i]
          let profile_data = ''

          // get node information
          const customNodesResponse = await getCustomNodes(
            mapId,
            profile.profile_url
          )
          const customNodesResponseData = await customNodesResponse.json()
          if (!response.ok && response.status !== 404) {
            alert(
              `Delete Profile Error: ${
                customNodesResponse.status
              } ${JSON.stringify(customNodesResponseData)}`
            )
          }

          let profileObject = {
            profile_data: profile_data,
            index_data: profile,
            data: {
              map_id: mapId,
              tag_slug: tagSlug,
              is_available: true,
              unavailable_message: ''
            }
          }

          // handle deleted profiles
          if (profile.status === 'deleted') {
            if (customNodesResponse.status === 404) {
              continue
            }

            // if customNodesResponse is not 404, need to get the node_id and post_id
            profileObject.data.node_id = customNodesResponseData[0].id
            profileObject.data.post_id = customNodesResponseData[0].post_id

            // if the status is deleted, we need to get profile_data from wpdb
            profileObject.profile_data = customNodesResponseData[0].profile_data

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
            }

            // delete node from nodes table
            const deleteResponse = await deleteCustomNodes(profileObject)

            if (!deleteResponse.ok) {
              const deleteResponseData = await deleteResponse.json()
              alert(
                `Delete Node Error: ${deleteResponse.status} ${JSON.stringify(
                  deleteResponseData
                )}`
              )
            }

            // put deleted profile in list
            deletedProfiles.push(profileObject)
            continue
          }

          let fetchProfileError = ''
          if (profile.profile_url) {
            try {
              const response = await fetch(profile.profile_url)
              if (response.ok) {
                profile_data = await response.json()
              } else {
                fetchProfileError = 'STATUS-' + response.status
              }
            } catch (error) {
              if (error.message === 'Failed to fetch') {
                fetchProfileError = 'CORS'
              } else {
                fetchProfileError = 'UNKNOWN'
              }
            }
          }

          if (profile_data === '') {
            profileObject.data.is_available = false
            profileObject.data.unavailable_message = fetchProfileError
          }

          // give extra data to profile
          profileObject.id = currentId
          profileObject.profile_data = profile_data
          profileObject.data.status = 'new'
          profileObject.data.extra_notes = ''

          if (customNodesResponse.status === 404) {
            const profileResponse = await saveCustomNodes(profileObject)

            if (!profileResponse.ok) {
              const profileResponseData = await profileResponse.json()
              alert(
                `Unable to save profiles to wpdb, errors: ${
                  profileResponse.status
                } ${JSON.stringify(
                  profileResponseData
                )}. Please delete the map and try again.`
              )
              return
            }
          } else {
            profileObject.data.status = customNodesResponseData[0].status
            profileObject.data.node_id = customNodesResponseData[0].id
            profileObject.data.post_id = customNodesResponseData[0].post_id

            if (
              customNodesResponseData[0].last_updated !==
              profile.last_updated.toString()
            ) {
              profileObject.data.extra_notes = 'see updates'
            }

            // ignore if status is not new and it doesn't have updates
            if (
              customNodesResponseData[0].status !== 'new' &&
              profileObject.data.extra_notes !== 'see updates'
            ) {
              continue
            }
          }

          currentId++
          dataWithIds.push(profileObject)
        }
      }

      // handle unavailable profiles
      if (unavailableProfiles.length > 0) {
        for (let i = 0; i < unavailableProfiles.length; i++) {
          // update progress
          progress += progressStep
          if (progress > 100) {
            setProgress(100)
          } else {
            setProgress(progress)
          }

          const profile = unavailableProfiles[i]

          // check this profile is already in the list or not
          const isProfileInList = dataWithIds.find(
            profileInList => profileInList.data.node_id === profile.id
          )

          if (isProfileInList) {
            continue
          }

          // fetch the data, if it's available now, add it to the list
          let profile_data = ''
          if (profile.profile_url) {
            try {
              const response = await fetch(profile.profile_url)
              if (response.ok) {
                profile_data = await response.json()
              }
            } catch (error) {
              // if there is an error, don't add it to the list
              continue
            }
          }

          if (profile_data !== '') {
            let profileObject = {
              id: currentId,
              profile_data: profile_data,
              index_data: profile,
              data: {
                map_id: mapId,
                tag_slug: tagSlug,
                node_id: profile.id,
                status: profile.status,
                is_available: true,
                unavailable_message: ''
              }
            }
            currentId++
            dataWithIds.push(profileObject)
          }
        }
      }

      if (deletedProfiles.length === 0 && dataWithIds.length === 0) {
        setProfileList([])
        alert(`No update profiles found.`)
        return
      }

      // if it only has deleted profiles, update map timestamp
      if (deletedProfiles.length > 0 && dataWithIds.length === 0) {
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
      }

      setDeletedProfiles(deletedProfiles)
      setProfileList(dataWithIds)
    } catch (error) {
      alert(`Retrieve node error: ${error}`)
    } finally {
      setIsLoading(false)
      setProgress(0)
    }
  }

  const handleEditNodes = async mapId => {
    setIsLoading(true)
    setIsRetrieving(true)
    setDeletedProfiles([])

    try {
      // get nodes from WP
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
            unavailable_message: profile.unavailable_message
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
    setDeletedProfiles([])
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
      setProfileList([])
      setIsLoading(false)
      setMapIdToDelete(null)
      await getMaps()
    }
  }

  const handleDeleteCancel = () => {
    setIsPopupOpen(false)
    setMapIdToDelete(null)
  }

  return (
    <div>
      <h2 className="text-xl">Map Data</h2>
      <button
        className={`my-1 mx-2 max-w-fit rounded-full bg-indigo-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-indigo-400 disabled:opacity-75 mt-5 ${
          isLoading ? 'opacity-50 cursor-not-allowed' : ''
        }`}
        onClick={() => handleCreate()}
      >
        {isLoading ? 'Loading' : 'Create Map'}
      </button>
      {maps.length > 0 ? (
        maps.map((map, index) => (
          <div className="bg-white p-4 rounded shadow-md mt-4" key={index}>
            <h2 className="text-xl font-semibold mb-2">{map.name}</h2>
            <p>
              <strong>Query URL:</strong>{' '}
              <a href={map.index_url + map.query_url}>
                {map.index_url + map.query_url}
              </a>
            </p>
            <p>
              <strong>Shortcode:</strong> [murmurations_map tag_slug=&quot;
              {map.tag_slug}&quot; height=&quot;50&quot; view=&quot;map&quot;]
            </p>
            <p>
              <strong>Map Center:</strong>{' '}
              {map.map_center_lon + ',' + map.map_center_lat}
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
            <div className="box-border flex flex-wrap xl:min-w-max flex-row mt-4 justify-between">
              <button
                className={`my-1 mx-2 max-w-fit rounded-full bg-yellow-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-yellow-400 disabled:opacity-75 ${
                  isLoading ? 'opacity-50 cursor-not-allowed' : ''
                }`}
                onClick={() =>
                  handleRetrieve(
                    map.id,
                    map.index_url + map.query_url,
                    map.tag_slug
                  )
                }
              >
                {isLoading ? 'Loading' : 'Retrieve'}
              </button>
              <button
                className={`my-1 mx-2 max-w-fit rounded-full bg-amber-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-amber-400 disabled:opacity-75 ${
                  isLoading ? 'opacity-50 cursor-not-allowed' : ''
                }`}
                onClick={() => handleEditNodes(map.id)}
              >
                {isLoading ? 'Loading' : 'Edit Nodes'}
              </button>
              <button
                className="my-1 mx-2 max-w-fit rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75"
                onClick={() => handleEditMap(map.id)}
              >
                Edit Map
              </button>
              <button
                className={`my-1 mx-2 max-w-fit rounded-full bg-red-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-red-400 disabled:opacity-75 ${
                  isLoading ? 'opacity-50 cursor-not-allowed' : ''
                }`}
                onClick={() => handleDelete(map.id)}
              >
                {isLoading ? 'Loading' : 'Delete'}
              </button>
            </div>
          </div>
        ))
      ) : (
        <p>No maps found.</p>
      )}
      {isPopupOpen && (
        <div className="fixed inset-0 flex items-center justify-center z-50">
          <div className="bg-white p-8 rounded shadow-lg">
            <p className="text-xl">Are you sure you want to delete?</p>
            <div className="mt-4">
              <button
                onClick={handleDeleteConfirm}
                className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded mr-4 text-lg"
              >
                Confirm
              </button>
              <button
                onClick={handleDeleteCancel}
                className="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded text-lg"
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
  isLoading: PropTypes.bool.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  setIsRetrieving: PropTypes.func.isRequired,
  setProfileList: PropTypes.func.isRequired,
  setCurrentTime: PropTypes.func.isRequired,
  setProgress: PropTypes.func.isRequired,
  setDeletedProfiles: PropTypes.func.isRequired
}
