import MapSettings from './MapSettings'
import DataSource from './DataSource'
import ProgressBar from './ProgressBar'
import { createId } from '@paralleldrive/cuid2'
import { saveCustomMap, saveCustomNodes } from '../utils/api'
import PropTypes from 'prop-types'
import { useState } from 'react'
import {fetchProfileData, validateProfileData} from '../utils/fetchProfile'

const excludedKeys = [
  'data_url',
  'map_id',
  'map_name',
  'map_center_lat',
  'map_center_lon',
  'map_scale'
]

export default function CreateData({
  formData,
  handleInputChange,
  setIsLoading,
  setIsRetrieving,
  setIsMapSelected,
  setProfileList,
  progress,
  setProgress,
  isLoading,
  setCurrentTime,
  getMaps
}) {
  const [selectedCountry, setSelectedCountry] = useState([])

  const handleSubmit = async event => {
    window.scrollTo(0, 0)
    event.preventDefault()
    setIsLoading(true)
    setIsRetrieving(false)

    const queryParams = []
    for (const key in formData) {
      if (formData[key] !== '' && !excludedKeys.includes(key)) {
        queryParams.push(
          `${encodeURIComponent(key)}=${encodeURIComponent(formData[key])}`
        )
      }
    }

    // Handle country array
    if (selectedCountry.length > 0) {
      queryParams.push(
        `${encodeURIComponent('country')}=${encodeURIComponent(
          selectedCountry.join(',')
        )}`
      )
    }

    const queryString = queryParams.join('&')
    const pageQueries = 'page=1&page_size=500'
    const urlWithParams = `${formData.data_url}?${
      queryString ? `${queryString}&` : ''
    }${pageQueries}`

    try {
      setCurrentTime(new Date().getTime().toString())
      const response = await fetch(urlWithParams)
      if (response.ok) {
        const responseData = await response.json()
        if (responseData.data && responseData.data.length === 0) {
          alert('Error: No data found. Please try again.')
          return
        }
        if (
          responseData.meta &&
          responseData.meta.total_pages &&
          responseData.meta.total_pages > 2
        ) {
          alert('Error: Too many pages of data. Please narrow your search.')
          return
        }

        // If we find any valid responses, we can save the map to WP Map
        const tagSlug = 'murm_' + createId()
        const mapResponse = await saveCustomMap(
          formData.map_name,
          tagSlug,
          formData.data_url,
          urlWithParams.replace(formData.data_url, ''),
          formData.map_center_lat,
          formData.map_center_lon,
          formData.map_scale
        )
        const mapResponseData = await mapResponse.json()
        if (!mapResponse.ok) {
          alert(
            `Map Error: ${mapResponse.status} ${JSON.stringify(
              mapResponseData
            )}`
          )
          return
        }

        // Set the profileList and save the data to wpdb
        const profiles = responseData.data

        const dataWithIds = []
        const progressStep = 100 / profiles.length
        let currentId = 1
        for (let i = 0; i < profiles.length; i++) {
          // Update progress
          progress = (i + 1) * progressStep
          if (progress > 100) {
            setProgress(100)
          } else {
            setProgress(progress)
          }

          // If the status is deleted, continue
          if (profiles[i].status === 'deleted') {
            continue
          }

          const profile = profiles[i]
          let { profileData, fetchProfileError } = await fetchProfileData(
            profile.profile_url
          )
          let profileObject = {
            id: currentId,
            profile_data: profileData,
            index_data: profile,
            data: {
              map_id: mapResponseData.map_id,
              tag_slug: tagSlug,
              status: 'new',
              is_available: 1,
              has_authority: 1
            }
          }

          // Set availability
          if (profileData === '') {
            profileObject.data.is_available = 0
            profileObject.data.unavailable_message = fetchProfileError
          }

          // Send profile data to validate
          if (profileData) {
            const isValid = await validateProfileData(profileData, formData?.data_url)
            if (!isValid) {
              profileObject.data.is_available = 0
              profileObject.data.unavailable_message = 'Invalid Profile Data'
            }
          }

          // Save data to wpdb
          // todo: status needs to update according to the settings
          const profileResponse = await saveCustomNodes(profileObject)

          const profileResponseData = await profileResponse.json()
          if (!profileResponse.ok) {
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

          // Set node_id
          profileObject.data.node_id = profileResponseData.node_id

          currentId++
          dataWithIds.push(profileObject)
        }
        setProfileList(dataWithIds)
      } else {
        alert(`Error: ${response.status} ${response}`)
      }
    } catch (error) {
      setIsMapSelected(false)
      alert(
        `Handle Submit error: ${error}, please delete the map and retry again.`
      )
    } finally {
      setIsMapSelected(true)
      setIsLoading(false)
      setProgress(0)
      await getMaps()
    }
  }

  const handleCancel = () => {
    setIsMapSelected(false)
    window.scrollTo(0, 0)
  }

  return (
    <div>
      {isLoading && <ProgressBar progress={progress} />}
      <h2 className="text-2xl">Create a Map or Directory</h2>
      <p className="my-2 text-base">
        Import nodes from the distributed Murmurations network to create your
        own custom maps and directories.
      </p>
      <form onSubmit={handleSubmit} className="py-6">
        <div className="mb-8">
          <label
            className="mb-2 block text-base font-bold text-gray-700"
            htmlFor="map_name"
          >
            Map/Directory Name
          </label>
          <input
            type="text"
            id="map_name"
            name="map_name"
            value={formData.map_name}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
            required={true}
          />
          <div className="mt-1">
            A familiar name to make it easy for you to identify
          </div>
        </div>
        <MapSettings
          formData={formData}
          handleInputChange={handleInputChange}
        />
        <DataSource
          formData={formData}
          handleInputChange={handleInputChange}
          selectedCountry={selectedCountry}
          setSelectedCountry={setSelectedCountry}
        />
        <div className="mt-6">
          <button
            type="submit"
            className={`mx-4 rounded-full bg-orange-500 px-4 py-2 text-lg font-bold text-white hover:scale-110 hover:bg-orange-400 active:scale-90 disabled:opacity-75 ${
              isLoading ? 'cursor-not-allowed opacity-50' : ''
            }`}
          >
            {isLoading ? 'Creating...' : 'Create'}
          </button>
          <button
            onClick={handleCancel}
            className="mx-4 rounded-full bg-gray-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-gray-400 active:scale-90 disabled:opacity-75"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  )
}

CreateData.propTypes = {
  formData: PropTypes.object.isRequired,
  handleInputChange: PropTypes.func.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  setIsRetrieving: PropTypes.func.isRequired,
  setIsMapSelected: PropTypes.func.isRequired,
  setProfileList: PropTypes.func.isRequired,
  progress: PropTypes.number.isRequired,
  setProgress: PropTypes.func.isRequired,
  isLoading: PropTypes.bool.isRequired,
  setCurrentTime: PropTypes.func.isRequired,
  getMaps: PropTypes.func.isRequired
}
