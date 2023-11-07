export const removeSelectedProfiles = (profileList, selectedIds) => {
  return profileList.filter(profile => !selectedIds.includes(profile.id))
}

export const removeUnselectedProfiles = (profileList, selectedIds) => {
  return profileList.filter(profile => selectedIds.includes(profile.id))
}
