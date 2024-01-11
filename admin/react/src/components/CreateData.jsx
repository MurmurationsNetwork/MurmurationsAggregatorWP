import MapSettings from './MapSettings'
import DataSource from './DataSource'
import ProgressBar from './ProgressBar'
import { createId } from '@paralleldrive/cuid2'
import { getProxyData, saveCustomMap, saveCustomNodes } from '../utils/api'
import PropTypes from 'prop-types'
import { useState } from 'react'
import {whiteList} from "../data/whiteList";

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

    // handle country array
    if (selectedCountry.length > 0) {
      queryParams.push(
        `${encodeURIComponent('country')}=${encodeURIComponent(
          selectedCountry.join(',')
        )}`
      )
    }

    const queryString = queryParams.join('&')
    const pageQueries = 'page=1&page_size=500'

    const urlWithParams =
      formData.data_url +
      '?' +
      (queryString ? `${queryString}&` : '') +
      pageQueries

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

        // we have a valid response, we can save the map to the server
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

        // set the profileList and save the data to wpdb
        const profiles = responseData.data

        // domain authority - we need to check primary_url and profile_url is match or not
        let primaryUrlCount = new Map()
        for (let i = 0; i < profiles.length; i++) {
          // if the status is deleted, continue
          if (profiles[i].status === 'deleted') {
            continue
          }
          if (profiles[i].primary_url) {
            primaryUrlCount.set(profiles[i].primary_url, (primaryUrlCount.get(profiles[i].primary_url) || 0) + 1)
          }
        }

        const dataWithIds = []
        const progressStep = 100 / profiles.length
        let currentId = 1
        for (let i = 0; i < profiles.length; i++) {
          // update progress
          if ((i + 1) * progressStep > 100) {
            setProgress(100)
          } else {
            setProgress((i + 1) * progressStep)
          }

          // if the status is deleted, continue
          if (profiles[i].status === 'deleted') {
            continue
          }

          const profile = profiles[i]
          let profile_data = ''
          let fetchProfileError = ''
          if (profile.profile_url) {
            try {
              const response = await getProxyData(profile.profile_url)
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

          // construct the profile object
          let profileObject = {
            id: currentId,
            profile_data: profile_data,
            index_data: profile,
            data: {
              map_id: mapResponseData.map_id,
              tag_slug: tagSlug,
              status: 'new',
              is_available: true,
              has_authority: true,
            }
          }

          // set availability
          if (profile_data === '') {
            profileObject.data.is_available = false
            profileObject.data.unavailable_message = fetchProfileError
          }

          // set domain authority
          if (profile.profile_url && profile.primary_url) {
            if (primaryUrlCount.get(profile.primary_url) > 1) {
              try {
                const addDefaultScheme = (url) => {
                  if (!url.startsWith("http://") && !url.startsWith("https://")) {
                    return "https://" + url;
                  }
                  return url;
                };

                // check the domain name is match or not
                const primaryUrl = new URL(addDefaultScheme(profile.primary_url));
                const profileUrl = new URL(addDefaultScheme(profile.profile_url));

                // only get last two parts which is the domain name
                const primaryDomain = primaryUrl.hostname.split('.').slice(-2).join('.');
                const profileDomain = profileUrl.hostname.split('.').slice(-2).join('.');

                // Compare the domain names to check if they match
                if (primaryDomain !== profileDomain && !whiteList.includes(profileDomain)) {
                  profileObject.data.has_authority = false;
                }
              } catch (error) {
                // Handle the error if the URL is invalid
                console.error("Invalid URL:", error.message);
              }
            }
          }

          // save data to wpdb
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
              `
                }Unable to save profiles to wpdb, errors: ${
                  profileResponse.status
                } ${JSON.stringify(
                  profileResponseData
                )}. Please delete the map and try again.`
            )
            return
          }

          // set node_id
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
